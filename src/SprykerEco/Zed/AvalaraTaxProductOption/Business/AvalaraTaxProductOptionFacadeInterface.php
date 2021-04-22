<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxProductOption\Business;

use Generated\Shared\Transfer\AvalaraCreateTransactionRequestTransfer;
use Generated\Shared\Transfer\AvalaraCreateTransactionResponseTransfer;
use Generated\Shared\Transfer\CalculableObjectTransfer;

interface AvalaraTaxProductOptionFacadeInterface
{
    /**
     * Specification:
     * - Expands `AvalaraCreateTransactionRequestTransfer` with product option data.
     * - Requires `CalculableObjectTransfer.items.productOption.avalaraTaxCode` and `CalculableObjectTransfer.items.productOption.sku` to be set.
     * - Requires `AvalaraCreateTransactionRequestTransfer.transaction` to be set.
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
    ): AvalaraCreateTransactionRequestTransfer;

    /**
     * Specification:
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
    public function calculateProductOptionTax(
        CalculableObjectTransfer $calculableObjectTransfer,
        AvalaraCreateTransactionResponseTransfer $avalaraCreateTransactionResponseTransfer
    ): CalculableObjectTransfer;
}
