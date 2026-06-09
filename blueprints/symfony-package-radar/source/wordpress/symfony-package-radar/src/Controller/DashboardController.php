<?php

namespace App\Controller;

use App\Service\SymfonyPackageRadar;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    private const PACKAGES = [
        'symfony/framework-bundle',
        'symfony/http-client',
        'symfony/twig-bundle',
    ];

    #[Route('/', name: 'dashboard')]
    public function dashboard(SymfonyPackageRadar $radar): Response
    {
        return $this->render('dashboard.html.twig', [
            'packages' => $radar->inspect(self::PACKAGES),
            'controllerSource' => highlight_file(__FILE__, true),
        ]);
    }

    #[Route('/api/packages', name: 'packages_json')]
    public function packages(SymfonyPackageRadar $radar): JsonResponse
    {
        return $this->json([
            'packages' => $radar->inspect(self::PACKAGES),
            'served_by' => 'Symfony running inside Playground',
        ]);
    }
}
