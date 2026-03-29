<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
{
    #[Route('/', name: 'event_index')]
    public function index(EventRepository $eventRepository): Response
    {
        $upcoming=$eventRepository->upcomingEvents();
        $pastEvents = $eventRepository->pastEvents();

        return $this->render('events/index.html.twig', [
            'pastEvents' => $pastEvents,
            'upcoming' => $upcoming,
        ]);
    }
    #[Route('/events', name: 'app_events')]
    public function events(EventRepository $eventRepository): Response
    {
        $upcoming=$eventRepository->upcomingEvents();
        $pastEvents = $eventRepository->pastEvents();

        return $this->render('events/index.html.twig', [
            'pastEvents' => $pastEvents,
            'upcoming' => $upcoming,
        ]);
    }

    #[Route('/event/{id}', name: 'event_details')]
    public function details(int $id,EventRepository $eventRepository): Response
    {
        $event = $eventRepository->find($id);
        return $this->render('events/details.html.twig', [
            'event' => $event,
        ]);
    }
}
