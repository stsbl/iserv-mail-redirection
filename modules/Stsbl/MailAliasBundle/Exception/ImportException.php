<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Exception;

/*
 * The MIT License
 *
 * Copyright 2021 Felix Jacobi.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Exception thrown on <tt>Importer</tt> errors.
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @licenses MIT license <https://opensource.org/licenses/MIT>
 */
final class ImportException extends \RuntimeException implements CsvFileExceptionInterface
{
    public const MESSAGE_INVALID_MIME_TYPE = 'The uploaded file is not a plain text file.';

    public const MESSAGE_INVALID_COLUMN_AMOUNT = 'The CSV file has an invalid amount of columns.';

    public const MESSAGE_FILE_IS_NULL = 'The uploaded file is undefined.';

    public function __construct(
        ?string $message = '',
        int $code = 0,
        private readonly ?int $fileLine = null,
        private readonly ?int $columnAmount = null,
        private readonly ?int $expected = null,
        \Throwable $previous = null
    ) {

        parent::__construct($message, $code, $previous);
    }

    public function getFileLine(): ?int
    {
        return $this->fileLine;
    }

    public function getColumnAmount(): ?int
    {
        return $this->columnAmount;
    }

    public function getExpected(): ?int
    {
        return $this->expected;
    }

    /**
     * Creates exception with predefined message for invalid mime type
     */
    public static function invalidMimeType(): self
    {
        return new self(self::MESSAGE_INVALID_MIME_TYPE);
    }

    /**
     * Creates exception with predefined message for invalid column number.
     */
    public static function invalidColumnAmount(int $line, int $columnAmount, int $expected): self
    {
        return new self(self::MESSAGE_INVALID_COLUMN_AMOUNT, 0, $line, $columnAmount, $expected);
    }

    /**
     * Creates exception with predefined message for not defined uploaded file.
     */
    public static function fileIsNull(): self
    {
        return new self(self::MESSAGE_FILE_IS_NULL);
    }
}
