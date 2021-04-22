<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxProductOption\Business;

use Generated\Shared\Transfer\AvalaraCreateTransactionRequestTransfer;
use Generated\Shared\Transfer\AvalaraCreateTransactionResponseTransfer;
use Generated\Shared\Transfer\CalculableObjectTransfer;
use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \SprykerEco\Zed\AvalaraTaxProductOption\Business\AvalaraTaxProductOptionBusinessFactory getFactory()
 */
class AvalaraTaxProductOptionFacade extends AbstractFacade implements AvalaraTaxProductOptionFacadeInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\AvalaraCreateTransactionRequestTransfer $avalaraCreateTransactionRequestTransfer
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     *
     * @return \Generated\Shared\Transfer\AvalaraCreateTransactionRequestTransfer
     */
    public function expandAvalaraCreateTransactionRequestWithProductOptions(
        AvalaraCreateTransactionRequestTransfer $avalaraCreateTransactionRequestTransfer,
        CalculableObjectTransfer $calculableObjectTransfer
    ): AvalaraCreateTransactionRequestTransfer {
        return $this->getFactory()
            ->createAvalaraCreateTransactionRequestExpander()
            ->expandAvalaraCreateTransactionRequestWithProductOptions($avalaraCreateTransactionRequestTransfer, $calculableObjectTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     * @param \Generated\Shared\Transfer\AvalaraCreateTransactionResponseTransfer $avalaraCreateTransactionResponseTransfer
     *
     * @return \Generated\Shared\Transfer\CalculableObjectTransfer
     */
    public function calculateProductOptionTax(
        CalculableObjectTransfer $calculableObjectTransfer,
        AvalaraCreateTransactionResponseTransfer $avalaraCreateTransactionResponseTransfer
    ): CalculableObjectTransfer {
        return $this->getFactory()
            ->createProductOptionTaxCalculatorStrategyResolver()
            ->resolve($calculableObjectTransfer)
            ->calculateTax($calculableObjectTransfer, $avalaraCreateTransactionResponseTransfer);
    }
}
