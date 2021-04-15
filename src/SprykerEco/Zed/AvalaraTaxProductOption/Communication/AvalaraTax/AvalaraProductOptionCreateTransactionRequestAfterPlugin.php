<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxProductOption\Communication\AvalaraTax;

use Generated\Shared\Transfer\AvalaraCreateTransactionResponseTransfer;
use Generated\Shared\Transfer\CalculableObjectTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use SprykerEco\Zed\AvalaraTaxExtension\Dependency\Plugin\CreateTransactionRequestAfterPluginInterface;

/**
 * @method \SprykerEco\Zed\AvalaraTaxProductOption\Business\AvalaraTaxProductOptionFacadeInterface getFacade()
 */
class AvalaraProductOptionCreateTransactionRequestAfterPlugin extends AbstractPlugin implements CreateTransactionRequestAfterPluginInterface
{
    /**
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
