<?php

namespace App\Form;

use App\Entity\Clocking;
use App\Entity\Project;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateClockingType extends
AbstractType
{

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array                                        $options
     *
     * @return void
     */
    public function buildForm(
        FormBuilderInterface $builder,
        array                $options
    ): void {
        $builder->add('clockingUser', EntityType::class, [
            'class' => User::class,
            'choice_label' => fn(?User $user) => $user?->getLastName() . ' ' . $user?->getFirstName(),
            'label' => 'Collaborateur',
        ]);

        $builder->add('date', DateType::class, [
            'label' => 'Date',
        ]);

        $builder->add('entries', CollectionType::class, [
            'entry_type' => ClockingEntryType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'label' => false,
        ]);

        $builder->add('submit', SubmitType::class, [
            'label' => 'Valider',
        ]);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     *
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Clocking::class,
            ]
        );
    }
}
