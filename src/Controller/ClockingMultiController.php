<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Clocking;
use App\Entity\ClockingEntry;
use App\Form\MultiClockingType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ClockingMultiController extends AbstractController
{
    #[Route('/clockings/multi', name: 'app_clocking_multi', methods: ['GET', 'POST'])]
    public function multiClocking(Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(MultiClockingType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data    = $form->getData();
            $project = $data['project'];
            $date    = $data['date'];

            foreach ($data['entries'] as $row) {
                $user     = $row['user'];
                $duration = $row['duration'];

                // 1) Récupérer ou créer le Clocking 
                $clocking = $em->getRepository(Clocking::class)->findOneBy([
                    'clockingUser' => $user,
                    'date'         => $date,
                ]);
                if (!$clocking) {
                    $clocking = new Clocking();
                    $clocking->setClockingUser($user);
                    $clocking->setDate($date);
                    $em->persist($clocking);
                }

                // 2) Ajouter une entrée 
                $entry = new ClockingEntry();
                $entry->setProject($project);
                $entry->setDuration($duration);
                $entry->setClocking($clocking);

                $clocking->addEntry($entry);
            }

            $em->flush();
            return $this->redirectToRoute('app_Clocking_list');
        }


        return $this->render('app/Clocking/multi_create.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
