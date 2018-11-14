<?php
// src/Stsbl/MailAliasBundle/Exception/ImportExecption.php
namespace Stsbl\MailAliasBundle\Exception;

/*
 * The MIT License
 *
 * Copyright 2018 Felix Jacobi.
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
class ImportException extends \RuntimeException implements CsvFileExceptionInterface
{
    const MESSAGE_INVALID_MIME_TYPE = 'The uploaded file is not a plain text file.';
    
    const MESSAGE_PATH_NOT_FOUND = 'The path of the uploaded file was not found.';
    
    const MESSAGE_INVALID_COLUMN_AMOUNT = 'The CSV file has an invalid amount of columns.';
    
    const MESSAGE_FILE_IS_NULL = 'The uploaded file is undefined.';
    
    /**
     * @var integer
     */
    private $fileLine;
    
    /**
     * @var integer
     */
    private $columnAmount;
    
    /**
     * @var integer
     */
    private $expected;
    
    /**
     * Fires up a new exception
     * 
     * @param string $message
     * @param integer $code
     * @param \Throwable $previous
     * @param integer $fileLine
     * @param integer $columnAmount
     * @param integer $expected
     */
    public function __construct($message = "", $code = 0, $fileLine = null, $columnAmount = null, $expected = null, \Throwable $previous = null) 
    {
        $this->fileLine = $fileLine;
        $this->columnAmount = $columnAmount;
        $this->expected = $expected;
        
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Get line
     * 
     * @return integer|null
     */
    public function getFileLine()
    {
        return $this->fileLine;
    }
    
    /**
     * Get number of columns
     * 
     * @return integer|null
     */
    public function getColumnAmount() 
    {
        return $this->columnAmount;
    }
    
    /**
     * Get acually amount of columns
     * 
     * @return integer|null
     */
    public function getExpected() 
    {
        return $this->expected;
    }
    
    /**
     * Creates exception with predefined message for invalid mime type
     * 
     * @return ImportException
     */
    public static function invalidMimeType()
    {
        return new self(self::MESSAGE_INVALID_MIME_TYPE);
    }
    
    /**
     * Creates exception with predefined message for not found uploaded file
     * 
     * @return ImportException
     */
    public static function pathNotFound()
    {
        return new self(self::MESSAGE_PATH_NOT_FOUND);
    }
    
    /**
     * Creates exception with predefined message for invalid column number.
     * 
     * @param integer $line
     * @param integer $columnAmount
     * @param integer $expected
     * @return ImportException
     */
    public static function invalidColumnAmount($line, $columnAmount, $expected)
    {
        return new self(self::MESSAGE_INVALID_COLUMN_AMOUNT, 0, $line, $columnAmount, $expected);
    }
    
    /**
     * Creates exception with predefined message for not defined uploaded file.
     * 
     * @return ImportException
     */
    public static function fileIsNull()
    {
        return new self(self::MESSAGE_FILE_IS_NULL);
    }
}
