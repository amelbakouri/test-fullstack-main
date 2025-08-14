<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;

class UserDurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn(User $u) => $u->getLastName() . ' ' . $u->getFirstName(),
                'label' => 'Collaborateur',
            ])
            ->add('duration', IntegerType::class, [
                'label' => 'Durée (heures)',
            ]);
    }
}
