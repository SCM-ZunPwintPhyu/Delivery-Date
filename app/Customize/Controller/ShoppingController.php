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
use Eccube\Service\OrderHelper;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Entity\Master\CsvType;
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
        OrderRepository $orderRepository,
        OrderHelper $orderHelper
    ) {
        $this->cartService = $cartService;
        $this->mailService = $mailService;
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
        log_info('[注文完了] 注文完了画面を表示します.');

        // 受注IDを取得
        $orderId = $this->session->get(OrderHelper::SESSION_ORDER_ID);
        if (empty($orderId)) {
            log_info('[注文完了] 受注IDを取得できないため, トップページへ遷移します.');

            return $this->redirectToRoute('homepage');
        }

        $Order = $this->orderRepository->find($orderId);
        $filename = 'order_'.(new \DateTime())->format('YmdHis').'.csv';
        $this->exportCsv($request, CsvType::CSV_TYPE_ORDER, $filename);
        $path = $_SERVER['DOCUMENT_ROOT'];
        
        mkdir($path.'\\'.$filename,0777);


        $filename = 'order_'.(new \DateTime())->format('YmdHis').'.csv';
        $this->exportCsv($request, CsvType::CSV_TYPE_ORDER, $filename);
        $path = $_SERVER['DOCUMENT_ROOT'].'\\'.$filename;
        mkdir($path,0777);

        $csv_handler = fopen ($filename."/$filename","w");

        // dd($csv_handler);

        fwrite ($csv_handler,$path);
        fclose ($csv_handler);

        // file_put_contents($filename, $Order);
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


   /**
     * @param Request $request
     * @param $csvTypeId
     * @param string $fileName
     *
     */
    protected function exportCsv(Request $request, $csvTypeId, $fileName)
    {
        // タイムアウトを無効にする.
        set_time_limit(0);
        // sql loggerを無効にする.
        $em = $this->entityManager;
        $em->getConfiguration()->setSQLLogger(null);
    }

}
