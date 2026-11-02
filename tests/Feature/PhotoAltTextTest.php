<?php

namespace Tests\Feature;

use App\Exceptions\AltTextUnavailable;
use App\Filament\Resources\Photos\Pages\CreatePhoto;
use App\Filament\Resources\Photos\Pages\EditPhoto;
use App\Models\Photo;
use App\Models\User;
use App\Support\AltText;
use App\Support\PhotoStore;
use Filament\Actions\Testing\TestAction;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery\MockInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tests\TestCase;

/** Opisy alternatywne zdjęć proponowane przez AI (Anthropic API) w panelu. Bez sieci: API zastępuje podstawiony transport. */
class PhotoAltTextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Photo::DISK);
        $this->seed();
        $this->actingAs(User::factory()->create());
        config(['services.anthropic.key' => 'sk-ant-test', 'services.anthropic.alt_model' => 'claude-opus-5-5']);
    }

    /** Transport HTTP, który zapamiętuje zapytanie i zwraca podaną odpowiedź API. */
    private function transport(array $body): ClientInterface
    {
        return new class($body) implements ClientInterface
        {
            public ?RequestInterface $request = null;

            public function __construct(private array $body) {}

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->request = $request;

                return new Response(200, ['Content-Type' => 'application/json'], json_encode($this->body));
            }
        };
    }

    private function message(string $text, string $stopReason = 'end_turn'): array
    {
        return [
            'id' => 'msg_test',
            'type' => 'message',
            'role' => 'assistant',
            'model' => 'claude-opus-5-5',
            'content' => [['type' => 'text', 'text' => $text]],
            'stop_reason' => $stopReason,
            'stop_sequence' => null,
            'usage' => ['input_tokens' => 1200, 'output_tokens' => 20],
        ];
    }

    public function test_the_api_gets_a_small_webp_copy_and_the_answer_is_cleaned(): void
    {
        $transport = $this->transport($this->message('„Zdjęcie przedstawia jasne wnętrze gabinetu z fotelem zabiegowym.”'));
        $file = UploadedFile::fake()->image('gabinet.jpg', 2400, 1600);

        $alt = (new AltText(app(PhotoStore::class), $transport))->describe($file->getRealPath());

        $this->assertSame('Jasne wnętrze gabinetu z fotelem zabiegowym.', $alt);

        $request = $transport->request;
        $body = json_decode((string) $request->getBody(), true);
        $this->assertStringEndsWith('/v1/messages', $request->getUri()->getPath());
        $this->assertStringContainsString('sk-ant-test', $request->getHeaderLine('authorization').$request->getHeaderLine('x-api-key'));
        $this->assertStringContainsString('server-side-fallback-2026-07-01', $request->getHeaderLine('anthropic-beta'));
        $this->assertSame('claude-opus-5-5', $body['model']);
        $this->assertSame('default', $body['fallbacks']);
        $this->assertSame('low', $body['output_config']['effort']);

        // Do API trafia pomniejszona kopia WebP (najwyżej 1024 px), nie oryginał z metadanymi.
        $source = $body['messages'][0]['content'][0]['source'];
        $this->assertSame(['base64', 'image/webp'], [$source['type'], $source['media_type']]);
        $image = base64_decode($source['data']);
        $this->assertSame('WEBP', substr($image, 8, 4));
        $this->assertSame(1024, getimagesizefromstring($image)[0]);
    }

    public function test_a_refusal_is_reported_instead_of_saved(): void
    {
        $transport = $this->transport($this->message('', 'refusal'));
        $file = UploadedFile::fake()->image('x.jpg', 800, 600);

        $this->expectException(AltTextUnavailable::class);

        (new AltText(app(PhotoStore::class), $transport))->describe($file->getRealPath());
    }

    public function test_without_a_key_nothing_is_sent(): void
    {
        config(['services.anthropic.key' => null]);
        $file = UploadedFile::fake()->image('x.jpg', 800, 600);

        $this->expectException(AltTextUnavailable::class);

        app(AltText::class)->describe($file->getRealPath());
    }

    public function test_answers_are_trimmed_to_one_clean_sentence(): void
    {
        $this->assertSame('Dłonie lekarki przy fotelu', AltText::clean("  \"Na zdjęciu widać: dłonie lekarki\n przy fotelu\"  "));
        $this->assertSame('Wazon z suszonymi kwiatami', AltText::clean('Fotografia pokazuje wazon z suszonymi kwiatami'));
        $this->assertSame(AltText::MAX_LENGTH, mb_strlen(AltText::clean(str_repeat('ściana ', 80))));
    }

    public function test_without_a_key_the_description_is_required_and_there_is_no_ai_button(): void
    {
        config(['services.anthropic.key' => null]);

        Livewire::test(CreatePhoto::class)
            ->assertActionHidden(TestAction::make('describeWithAi')->schemaComponent('alt'))
            ->fillForm(['file' => UploadedFile::fake()->image('x.jpg', 1200, 800), 'alt' => ''])
            ->call('create')
            ->assertHasFormErrors(['alt' => 'required']);

        $this->assertSame(0, Photo::count());
    }

    public function test_an_empty_description_is_written_by_ai_on_upload(): void
    {
        $this->mock(AltText::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('describe')->once()->andReturn('Jasne wnętrze gabinetu z fotelem zabiegowym'));

        Livewire::test(CreatePhoto::class)
            ->fillForm(['file' => UploadedFile::fake()->image('gabinet.jpg', 1200, 800), 'alt' => ''])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified('Opis zdjęcia napisało AI');

        $this->assertSame('Jasne wnętrze gabinetu z fotelem zabiegowym', Photo::sole()->alt);
    }

    public function test_a_failed_ai_description_keeps_the_form_open(): void
    {
        $this->mock(AltText::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('describe')->andThrow(new AltTextUnavailable('Brak połączenia z usługą AI.')));

        Livewire::test(CreatePhoto::class)
            ->fillForm(['file' => UploadedFile::fake()->image('gabinet.jpg', 1200, 800), 'alt' => ''])
            ->call('create')
            ->assertNotified('Wpisz opis zdjęcia');

        $this->assertSame(0, Photo::count());
    }

    public function test_a_typed_description_is_kept_and_ai_is_not_asked(): void
    {
        $this->mock(AltText::class, fn (MockInterface $mock) => $mock->shouldNotReceive('describe'));

        Livewire::test(CreatePhoto::class)
            ->fillForm(['file' => UploadedFile::fake()->image('gabinet.jpg', 1200, 800), 'alt' => 'Recepcja gabinetu'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('Recepcja gabinetu', Photo::sole()->alt);
    }

    public function test_the_button_proposes_a_description_for_a_photo_in_the_library(): void
    {
        $file = UploadedFile::fake()->image('x.jpg', 1200, 800);
        $photo = app(PhotoStore::class)->store($file->getRealPath(), 'Gabinet', 'Stary opis');

        $this->mock(AltText::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('describe')->once()->with($photo->localPath())->andReturn('Fotel zabiegowy przy oknie'));

        Livewire::test(EditPhoto::class, ['record' => $photo->getKey()])
            ->callAction(TestAction::make('describeWithAi')->schemaComponent('alt'))
            ->assertSchemaStateSet(['alt' => 'Fotel zabiegowy przy oknie'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Fotel zabiegowy przy oknie', $photo->fresh()->alt);
    }

    public function test_the_description_stays_required_when_editing(): void
    {
        $file = UploadedFile::fake()->image('x.jpg', 1200, 800);
        $photo = app(PhotoStore::class)->store($file->getRealPath(), 'Gabinet', 'Stary opis');

        Livewire::test(EditPhoto::class, ['record' => $photo->getKey()])
            ->fillForm(['alt' => ''])
            ->call('save')
            ->assertHasFormErrors(['alt' => 'required']);
    }
}
