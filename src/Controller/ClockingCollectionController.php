<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\CreateClockingType;
use App\Repository\ClockingRepository;
use App\Entity\Clocking;
use App\Entity\ClockingEntry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/clockings')]
class ClockingCollectionController extends
AbstractController
{

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Route('/create', name: 'app_Clocking_create', methods: ['GET', 'POST'])]
    public function createClocking(
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        $clocking = new Clocking();

        if ($request->isMethod('GET')) {
            $clocking->addEntry(new ClockingEntry());
        }
        $form = $this->createForm(CreateClockingType::class, $clocking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $existing = $entityManager->getRepository(Clocking::class)->findOneBy([
                'clockingUser' => $clocking->getClockingUser(),
                'date'         => $clocking->getDate(),
            ]);

            if ($existing) {
                foreach ($clocking->getEntries() as $entry) {
                    $e = new ClockingEntry();
                    $e->setProject($entry->getProject());
                    $e->setDuration((int)$entry->getDuration());
                    $e->setClocking($existing);
                    $existing->addEntry($e);
                    $entityManager->persist($e);
                }
                $entityManager->flush();
            } else {
                foreach ($clocking->getEntries() as $entry) {
                    $entry->setClocking($clocking);
                }
                $entityManager->persist($clocking);
                $entityManager->flush();
            }

            return $this->redirectToRoute('app_Clocking_list');
        }


        return $this->render('app/Clocking/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param \App\Repository\ClockingRepository $clockingRepository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Route('', name: 'app_Clocking_list', methods: ['GET'])]
    public function list(
        ClockingRepository $clockingRepository,
        Request $request
    ): Response {
        $order = strtoupper((string) $request->query->get('order', 'DESC'));
        if (!\in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'DESC';
        }

        $clockings = $clockingRepository->findBy([], ['date' => $order]);

        $grouped = [];

        foreach ($clockings as $clocking) {
            /** @var \App\Entity\Clocking $clocking */
            $dateKey = $clocking->getDate()->format('Y-m-d');

            foreach ($clocking->getEntries() as $entry) {
                /** @var \App\Entity\ClockingEntry $entry */
                $projectName = $entry->getProject()->getName();
                $user = $clocking->getClockingUser();

                $grouped[$dateKey][$projectName][] = [
                    'user'        => $user,
                    'duration'    => $entry->getDuration(),
                    'clocking_id' => $clocking->getId(),
                ];
            }
        }

        return $this->render('app/Clocking/list.html.twig', [
            'groupedClockings' => $grouped,
            'order'            => $order,
        ]);
    }
}
