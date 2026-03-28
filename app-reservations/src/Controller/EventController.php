<?php

namespace App\Controller;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
{
    #[Route('/events', name: 'app_events')]
    public function index(EventRepository $eventRepository): Response
    {
        $pastEvents = [
            [
                'title' => 'AI Summit 2024',
                'attendees' => '500+',
                'image' => 'images/hero1.jpg'
            ],
            [
                'title' => 'Web3 Hackathon',
                'attendees' => '300+',
                'image' => 'images/hackathon.png'
            ],
            [
                'title' => 'DevOps Session',
                'attendees' => '200+',
                'image' => 'images/session.jpg'
            ],
        ];

        return $this->render('events/index.html.twig', [
            'pastEvents' => $pastEvents,
        ]);
    }

    #[Route('/api/evenements', name: 'api_events')]
    public function apiEvents(EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findAll();
        $data = array_map(function ($event) {
            return [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'description' => $event->getDescription(),
                'dateDeb' => $event->getDateDeb() ? $event->getDateDeb()->format('Y-m-d H:i') : null,
                'dateFin' => $event->getDateFin() ? $event->getDateFin()->format('Y-m-d H:i') : null,
                'location' => $event->getLocation(),
                'seats' => $event->getSeats(),
                'image' => $event->getImage(),
            ];
        }, $events);

        return $this->json($data);
    }
}
