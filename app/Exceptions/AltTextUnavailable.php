<?php

namespace App\Exceptions;

use RuntimeException;

/** Usługa AI nie zaproponowała opisu zdjęcia. Komunikat jest po polsku i trafia do redaktorki. */
class AltTextUnavailable extends RuntimeException {}
