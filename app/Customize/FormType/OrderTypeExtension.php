<?php

namespace Customize\Form\Extension\Shopping;

use Eccube\Form\Type\Shopping\OrderType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints as Assert;
use Eccube\Common\EccubeConfig;

class OrderTypeExtension extends AbstractTypeExtension
{   
    /**
     * Constructor.
     *
     * @param EccubeConfig $config
     *
     */
    public function __construct(EccubeConfig $config) {
        $this->config = $config;
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('floor', IntegerType::class, [
                    'constraints' => [
                        new Assert\Range([
                            'min' => 1,
                            'max' => 99,
                        ]),
                        new Assert\Length(['max' => $this->config['eccube_int_len']]),
                        new Assert\Regex(['pattern' => '/^\d+$/']),
                    ],
            ])
            ->add('elevator', ChoiceType::class, [
                'choices' => [ 'common.yes' => 1, 'common.no' => 0 ],
                'placeholder' => 'common.select',
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'form_error.elevator_is_null']),
                ],
            ]);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return OrderType::class;
    }
}
