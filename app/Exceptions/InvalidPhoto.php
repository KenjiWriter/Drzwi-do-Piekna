<?php

namespace App\Exceptions;

use RuntimeException;

/** Plik nie nadaje się na zdjęcie do biblioteki. Komunikat jest po polsku i trafia do redaktorki. */
class InvalidPhoto extends RuntimeException {}
