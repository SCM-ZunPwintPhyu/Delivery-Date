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

namespace Customize\Controller\Mypage;

use Eccube\Controller\Mypage\MypageController as BaseController;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Order;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\CustomerFavoriteProductRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Service\CartService;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Eccube\Entity\ExportCsvRow;
use Eccube\Entity\Master\CsvType;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Eccube\Service\CsvExportService;


class MypageController extends BaseController
{
    /**
     * @var ProductRepository
     */
    protected $productRepository;

     /**
     * @var CsvExportService
     */
    protected $csvExportService;

    /**
     * @var CustomerFavoriteProductRepository
     */
    protected $customerFavoriteProductRepository;

    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var PurchaseFlow
     */
    protected $purchaseFlow;

    /**
     * MypageController constructor.
     *
     * @param OrderRepository $orderRepository
     * @param CustomerFavoriteProductRepository $customerFavoriteProductRepository
     * @param CartService $cartService
     * @param BaseInfoRepository $baseInfoRepository
     * @param PurchaseFlow $purchaseFlow
     * @param CsvExportService $csvExportService
     */
    public function __construct(
        OrderRepository $orderRepository,
        CustomerFavoriteProductRepository $customerFavoriteProductRepository,
        CartService $cartService,
        BaseInfoRepository $baseInfoRepository,
        PurchaseFlow $purchaseFlow,
        CsvExportService $csvExportService
    ) {
        $this->orderRepository = $orderRepository;
        $this->customerFavoriteProductRepository = $customerFavoriteProductRepository;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->cartService = $cartService;
        $this->purchaseFlow = $purchaseFlow;
        $this->csvExportService = $csvExportService;
    }

    /**
     * ???????????????.
     *
     * @Route("/mypage/receipe/{order_no}", name="receipe", methods={"PUT"})
     * @Template("Mypage/receipe.twig")
     */
    public function receipe(Request $request, $order_no)
    {
        $this->entityManager->getFilters()
            ->enable('incomplete_order_status_hidden');
        $Order = $this->orderRepository->findOneBy(
            [
                'order_no' => $order_no,
                'Customer' => $this->getUser(),
            ]
        );

        $event = new EventArgs(
            [
                'Order' => $Order,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MYPAGE_MYPAGE_HISTORY_INITIALIZE, $event);

        /** @var Order $Order */
        $Order = $event->getArgument('Order');

        if (!$Order) {
            throw new NotFoundHttpException();
        }

        return [
            'Order' => $Order,
        ];
    }


    /**
     * ??????CSV?????????.
     *
     *  @Route("/mypage/customer_order_export_order/{order_no}", name="customer_order_export_order")
     *
     * @param Request $request
     * @param string $orderCsvDownload
     *
     * @return StreamedResponse
     */
    public function exportOrder(Request $request,$order_no)
    {  
        $filename = 'order_'.(new \DateTime())->format('Ymd').$order_no.'.csv';
    	$path = $_SERVER['DOCUMENT_ROOT'].'\\'.$filename;
    	return $this->exportCsv($request,$order_no,$path, $filename);
    }


     /**
     * @param Request $request
     * @param $order_no
     * @param $path
     * @param string $fileName
     *
     * @return StreamedResponse
     */
    protected function exportCsv(Request $request,$order_no, $path, $fileName)
    {
        set_time_limit(0);

        $response = new StreamedResponse();
        $response->setCallback(function () use ($request,$order_no, $path) {
        
            // CSV????????????????????????.
            $this->csvExportService->initCsvType($path);
            // ??????????????????????????????????????????????????????.
            $qb = $this->csvExportService
                ->getOrderQueryBuilder($request,$order_no,$path);

            // ?????????????????????.
            $this->csvExportService->setExportQueryBuilder($qb);
            $this->csvExportService->exportData(function ($entity, $csvService) use ($request,$order_no) {
                $Csvs = $csvService->getCsvs();
                // $Order = $entity;
                $Order = $entity;
                $OrderItems = $Order->getOrderItems();
                foreach ($OrderItems as $OrderItem) {
                    $ExportCsvRow = new ExportCsvRow();
                    foreach ($Csvs as $Csv) {
                        $ExportCsvRow->setData($csvService->getData($Csv, $Order));
                        if ($ExportCsvRow->isDataNull()) {
                            // ?????????????????????????????????, ?????????????????????.
                            $ExportCsvRow->setData($csvService->getData($Csv, $OrderItem));
                        }

                        $event = new EventArgs(
                            [
                                'csvService' => $csvService,
                                'Csv' => $Csv,
                                'OrderItem' => $OrderItem,
                                'ExportCsvRow' => $ExportCsvRow,
                            ],
                            $request
                        );
                        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_ORDER_CSV_EXPORT_ORDER, $event);

                        $ExportCsvRow->pushData();
                    }

                    //$row[] = number_format(memory_get_usage(true));
                    $csvService->fputcsv($ExportCsvRow->getRow());
                }
            });
        });

        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename='.$fileName);
        $response->send();

        return $response;
    }

}
