<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Customize\Controller;

use Eccube\Form\Type\Shopping\OrderType;
use Eccube\Repository\OrderRepository;
use Eccube\Service\CartService;
use Eccube\Service\MailService;
use Customize\Service\CsvExportService;
use Eccube\Service\OrderHelper;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Entity\ExportCsvRow;
use Customize\EntityReplace\Csv;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Eccube\Controller\ShoppingController as BaseController;

class ShoppingController extends BaseController
{
    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var MailService
     */
    protected $mailService;

     /**
     * @var CsvExportService
     */
    protected $csvExportService;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    public function __construct(
        CartService $cartService,
        MailService $mailService,
        CsvExportService $csvExportService,
        OrderRepository $orderRepository,
        OrderHelper $orderHelper
    ) {
        $this->cartService = $cartService;
        $this->mailService = $mailService;
        $this->csvExportService = $csvExportService;
        $this->orderRepository = $orderRepository;
        $this->orderHelper = $orderHelper;
    }


    /**
     * 購入完了画面を表示する.
     *
     * @Route("/shopping/complete", name="shopping_complete")
     * @Template("Shopping/complete.twig")
     */
    public function complete(Request $request)
    {
        // 受注IDを取得
        $orderId = $this->session->get(OrderHelper::SESSION_ORDER_ID);
        if (empty($orderId)) {
            return $this->redirectToRoute('homepage');
        }
        $Order = $this->orderRepository->find($orderId);
        $filename = 'order_'.(new \DateTime())->format('Ymd').$orderId.'.csv';
        $filename1 = 'order_'.(new \DateTime())->format('Ymd').$orderId;
        $path =$_SERVER['DOCUMENT_ROOT'].'\\'.'csv_output'.'\\'.$filename1;
        $this->csvExportService->setCsvName($filename);
        $this->csvExportService->setDir($path); 
        $this->csvExportService->initCsvType(Csv::CSV_TYPE_ORDER_CSV);
        $this->csvExportService->exportHeader();
        $qb = $this->csvExportService->getProductQueryBuilder($request);
        

        $isOutOfStock = 0;
        $qb->resetDQLPart('select')
                ->resetDQLPart('orderBy')
                ->orderBy('p.update_date', 'DESC');

        if ($isOutOfStock) {
            $qb->select('p, pc')
                ->distinct();
        } else {
            $qb->select('p')
                ->distinct();
        }
        $this->csvExportService->setExportQueryBuilder($qb);

        $this->csvExportService->exportData(function ($entity, csvExportService $csvService) use ($request) {
            $Csvs = $csvService->getCsvs();
            

            /** @var $Order \Eccube\Entity\Order */
            $Orders = $entity;


            foreach ($Orders as $Order) {
                $ExportCsvRow = new ExportCsvRow();

                // CSV出力項目と合致するデータを取得.
                foreach ($Csvs as $Csv) {
                    // 商品データを検索.
                    $ExportCsvRow->setData($csvService->getData($Csv, $Order));
                    if ($ExportCsvRow->isDataNull()) {
                        // 商品規格情報を検索.
                        $ExportCsvRow->setData($csvService->getData($Csv, $Order));
                    }

                    $event = new EventArgs(
                        [
                            'csvService' => $csvService,
                            'Csv' => $Csv,
                            'Order' => $Order,
                            'ExportCsvRow' => $ExportCsvRow,
                        ],
                        $request
                    );
                    $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_PRODUCT_CSV_EXPORT, $event);

                    $ExportCsvRow->pushData();
                }
                $csvService->fputcsv($ExportCsvRow->getRow());
            }
        });

        $event = new EventArgs(
            [
                'Order' => $Order,
            ],
            $request
        );

        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_SHOPPING_COMPLETE_INITIALIZE, $event);

        if ($event->getResponse() !== null) {
            return $event->getResponse();
        }
        

        log_info('[注文完了] 購入フローのセッションをクリアします.');
        $this->orderHelper->removeSession();

        $hasNextCart = !empty($this->cartService->getCarts());

        log_info('[注文完了] 注文完了画面を表示しました. ', [$hasNextCart]);

        return [
            'Order' => $Order,
            'hasNextCart' => $hasNextCart,
        ];

    }
}
