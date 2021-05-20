<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxProductOption\Communication\Plugin\AvalaraTax;

use Generated\Shared\Transfer\AvalaraCreateTransactionResponseTransfer;
use Generated\Shared\Transfer\CalculableObjectTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use SprykerEco\Zed\AvalaraTaxExtension\Dependency\Plugin\CreateTransactionRequestAfterPluginInterface;

/**
 * @method \SprykerEco\Zed\AvalaraTaxProductOption\Business\AvalaraTaxProductOptionFacadeInterface getFacade()
 * @method \SprykerEco\Zed\AvalaraTaxProductOption\AvalaraTaxProductOptionConfig getConfig()
 */
class AvalaraProductOptionCreateTransactionRequestAfterPlugin extends AbstractPlugin implements CreateTransactionRequestAfterPluginInterface
{
    /**
     * {@inheritDoc}
     * - Calculates taxes for `ProductOptions` based on `AvalaraCreateTransactionResponseTransfer`.
     * - Sets tax data to `CalculableObjectTransfer.items.productOptions`.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     * @param \Generated\Shared\Transfer\AvalaraCreateTransactionResponseTransfer $avalaraCreateTransactionResponseTransfer
     *
     * @return \Generated\Shared\Transfer\CalculableObjectTransfer
     */
    public function execute(
        CalculableObjectTransfer $calculableObjectTransfer,
        AvalaraCreateTransactionResponseTransfer $avalaraCreateTransactionResponseTransfer
    ): CalculableObjectTransfer {
        return $this->getFacade()->calculateProductOptionTax(
            $calculableObjectTransfer,
            $avalaraCreateTransactionResponseTransfer
        );
    }
}
