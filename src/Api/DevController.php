<?php

declare(strict_types=1);

namespace App\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DevController extends AbstractController
{
    #[Route('/', name: 'app_dev')]
    public function index(): Response
    {
        return $this->render('dev/index.html.twig');
    }
}
