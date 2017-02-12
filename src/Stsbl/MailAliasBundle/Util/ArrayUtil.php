<?php
// src/Stsbl/MailAliasBundle/Util/ArrayUtil.php
namespace Stsbl\MailAliasBundle\Util;

/*
 * The MIT License
 *
 * Copyright 2017 Felix Jacobi.
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
 * Utils for Array Handling
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class ArrayUtil 
{
    /**
     * Compare two arrays with objects by compare the returns of callables/ inside of it.
     * 
     * @param array $firstArray
     * @param array $secondArray
     * @param string $firstCallable
     * @param string $secondCallable
     * @param array $firstCallableParameters
     * @param array $secondCallableParameters
     * @return array The objects which are in the $firstArray, but not in the $secondArray.
     */
    public static function getArrayDifferenceByCallables(array $firstArray, array $secondArray, $firstCallable, $secondCallable, array $firstCallableParameters = null, array $secondCallableParameters = null)
    {
        $notInSecondArray = [];
                    
        foreach ($firstArray as $firstArrayElement) {
            $inArray = false;
            
            foreach ($secondArray as $secondArrayElement) {
                if (!is_null($firstCallableParameters)) {
                    $firstResult = call_user_func_array([$firstArrayElement, $firstCallable], $firstCallableParameters);
                } else {
                    $firstResult = call_user_func([$firstArrayElement, $firstCallable]);
                }
                
                if (!is_null($secondCallableParameters)) {
                    $secondResult = call_user_func_array([$secondArrayElement, $secondCallable], $secondCallableParameters);
                } else {
                    $secondResult = call_user_func([$secondArrayElement, $secondCallable]);
                }
                
                if ($firstResult === $secondResult) {
                    $inArray = true;
                }
            }
            
            if (!$inArray) {
                $notInSecondArray[] = $firstArrayElement;
            }
        }
        
        return $notInSecondArray;
    }
}
