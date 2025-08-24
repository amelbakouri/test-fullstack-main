<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class UserDurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $b, array $o): void
    {
        $b
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn(User $u) => $u->getLastName().' '.$u->getFirstName(),
                'placeholder' => 'Choisir un collaborateur',
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('duration', IntegerType::class, [
                'label' => 'Durée (h)',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Positive(),
                ],
            ]);
    }
}
