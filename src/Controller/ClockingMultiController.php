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

class ClockingMultiController extends AbstractController
{
    #[Route('/clockings/multi', name: 'app_clocking_multi', methods: ['GET', 'POST'])]
    public function multiClocking(Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(MultiClockingType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $project = $data['project'];
            $date = $data['date'];

            foreach ($data['entries'] as $entry) {
                $user = $entry['user'];
                $duration = $entry['duration'];

                $clocking = new Clocking();
                $clocking->setClockingUser($user);
                $clocking->setDate($date);

                $entryEntity = new ClockingEntry();
                $entryEntity->setProject($project);
                $entryEntity->setDuration($duration);
                $entryEntity->setClocking($clocking);

                $clocking->addEntry($entryEntity);

                $em->persist($clocking);
            }

            $em->flush();

            return $this->redirectToRoute('app_Clocking_list');
        }

        return $this->render('app/Clocking/multi_create.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
