<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxProductOption\Business\StrategyResolver;

use Generated\Shared\Transfer\CalculableObjectTransfer;
use SprykerEco\Zed\AvalaraTaxProductOption\Business\Calculator\ProductOptionAvalaraTaxCalculatorInterface;

/**
 * @deprecated Exists for Backward Compatibility reasons only.
 */
interface ProductOptionTaxCalculatorStrategyResolverInterface
{
    /**
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     *
     * @return \SprykerEco\Zed\AvalaraTaxProductOption\Business\Calculator\ProductOptionAvalaraTaxCalculatorInterface
     */
    public function resolve(CalculableObjectTransfer $calculableObjectTransfer): ProductOptionAvalaraTaxCalculatorInterface;
}
