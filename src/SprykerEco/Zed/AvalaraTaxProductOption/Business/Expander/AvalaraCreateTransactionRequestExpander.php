<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxProductOption\Business\Expander;

use ArrayObject;
use Generated\Shared\Transfer\AvalaraCreateTransactionRequestTransfer;
use Generated\Shared\Transfer\AvalaraLineItemTransfer;
use Generated\Shared\Transfer\CalculableObjectTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use SprykerEco\Zed\AvalaraTaxProductOption\Business\Mapper\AvalaraLineItemMapperInterface;

class AvalaraCreateTransactionRequestExpander implements AvalaraCreateTransactionRequestExpanderInterface
{
    /**
     * @var \SprykerEco\Zed\AvalaraTaxProductOption\Business\Mapper\AvalaraLineItemMapperInterface
     */
    protected $avalaraLineItemMapper;

    /**
     * @param \SprykerEco\Zed\AvalaraTaxProductOption\Business\Mapper\AvalaraLineItemMapperInterface $avalaraLineItemMapper
     */
    public function __construct(AvalaraLineItemMapperInterface $avalaraLineItemMapper)
    {
        $this->avalaraLineItemMapper = $avalaraLineItemMapper;
    }

    /**
     * @param \Generated\Shared\Transfer\AvalaraCreateTransactionRequestTransfer $avalaraCreateTransactionRequestTransfer
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     *
     * @return \Generated\Shared\Transfer\AvalaraCreateTransactionRequestTransfer
     */
    public function expandAvalaraCreateTransactionRequestTransfer(
        AvalaraCreateTransactionRequestTransfer $avalaraCreateTransactionRequestTransfer,
        CalculableObjectTransfer $calculableObjectTransfer
    ): AvalaraCreateTransactionRequestTransfer {
        foreach ($calculableObjectTransfer->getItems() as $itemTransfer) {
            $productOptionTransfers = $itemTransfer->getProductOptions();
            if ($productOptionTransfers->count() === 0) {
                continue;
            }

            $avalaraCreateTransactionRequestTransfer = $this->addProductOptionsToAvalaraCreateTransactionRequestTransfer(
                $productOptionTransfers,
                $avalaraCreateTransactionRequestTransfer,
                $itemTransfer,
                $calculableObjectTransfer->getPriceModeOrFail()
            );
        }

        return $avalaraCreateTransactionRequestTransfer;
    }

    /**
     * @param \ArrayObject|\Generated\Shared\Transfer\ProductOptionTransfer[] $productOptionTransfers
     * @param \Generated\Shared\Transfer\AvalaraCreateTransactionRequestTransfer $avalaraCreateTransactionRequestTransfer
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     * @param string $priceMode
     *
     * @return \Generated\Shared\Transfer\AvalaraCreateTransactionRequestTransfer
     */
    protected function addProductOptionsToAvalaraCreateTransactionRequestTransfer(
        ArrayObject $productOptionTransfers,
        AvalaraCreateTransactionRequestTransfer $avalaraCreateTransactionRequestTransfer,
        ItemTransfer $itemTransfer,
        string $priceMode
    ): AvalaraCreateTransactionRequestTransfer {
        foreach ($productOptionTransfers as $productOptionTransfer) {
            $avalaraLineTransfer = $this->avalaraLineItemMapper->mapProductOptionTransferToAvalaraLineItemTransfer(
                $productOptionTransfer,
                new AvalaraLineItemTransfer(),
                $itemTransfer,
                $priceMode
            );

            $avalaraCreateTransactionRequestTransfer->getTransactionOrFail()->addLine($avalaraLineTransfer);
        }

        return $avalaraCreateTransactionRequestTransfer;
    }
}
