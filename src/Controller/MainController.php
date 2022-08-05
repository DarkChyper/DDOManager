<?php

namespace App\Controller;

use App\Entity\Ip;
use App\Repository\IpRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    #[Route(path: '/')]
    public function mainAction(IpRepository $ipRepository): JsonResponse
    {
        /** @var Ip $lastIp */
        $lastIp = $ipRepository->getLastIp()[0];

        return new JsonResponse([
            'updatedAt' => $lastIp->getUpdateAt()->format('Y-m-d H:i:s'),
            'checkedAt' => $lastIp->getCheckedAt()->format('Y-m-d H:i:s'),
        ]);
    }
}
