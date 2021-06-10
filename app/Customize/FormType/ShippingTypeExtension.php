<?php

namespace Customize\FormType;

use Eccube\Form\Type\Shopping\ShippingType;
use Customize\Entity\Calendar;
use Symfony\Component\Form\AbstractTypeExtension;
use Customize\Repository\CalendarRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Eccube\Common\EccubeConfig;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ShippingTypeExtension extends AbstractTypeExtension
{   
     /**
     * @var CalendarRepository
     */
    protected $CalendarRepository;

    /**
     * Constructor.
     *
     * @param EccubeConfig $config
     * @param CalendarRepository $calendarRepository
     *
     */
    public function __construct(EccubeConfig $config,CalendarRepository $calendarRepository) {
        $this->config = $config;
        $this->calendarRepository = $calendarRepository;
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
          // お届け日のプルダウンを生成
          $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $Shipping = $event->getData();
                if (is_null($Shipping) || !$Shipping->getId()) {
                    return;
                }
                // お届け日の設定
                $minDate = 0;
                $maxDate = [];
                
                // 配送時に最大となる商品日数を取得
                foreach ($Shipping->getProductOrderItems() as $detail) {
                    $maxDate[]=$detail->getProduct()->getDesireDeliveryDate();
                }
                
                $date = max($maxDate);
                $value=date_add(new \DateTime(),date_interval_create_from_date_string($date."days"));

                // 配達最大日数期間を設定
                $dateFormatter = \IntlDateFormatter::create(
                    'ja_JP@calendar=japanese',
                      \IntlDateFormatter::FULL,
                      \IntlDateFormatter::FULL,
                    'Asia/Tokyo',
                    \IntlDateFormatter::TRADITIONAL,
                    'E'
                );
                $Calendar = $this->calendarRepository->cal($value->format('Y-m-d'));
                $DateArray = [];
                foreach($Calendar as $calendar){
                    $DateArray[$calendar->getDate()->format('Y/m/d')] = $calendar->getDate()->format('Y/m/d').'('.$dateFormatter->format($calendar->getDate()).')';
                }
                $form = $event->getForm();
                $form
                    ->add(
                        'shipping_delivery_date',
                        ChoiceType::class,
                        [
                            'choices' => array_flip($DateArray),
                            'required' => false,
                            'placeholder' => 'common.select__unspecified',
                            'mapped' => false,
                            'data' => $Shipping->getShippingDeliveryDate() ? $Shipping->getShippingDeliveryDate()->format('Y/m/d') : null,
                            ]
                    );
            }
        );

        
    
    }
    
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ShippingType::class;
    }
}