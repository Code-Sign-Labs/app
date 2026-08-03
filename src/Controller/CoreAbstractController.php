<?php

namespace App\Controller;

use App\Service\AuditLogService;
use Doctrine\ORM\EntityManager;
use Framework\Http\AbstractController;
use Framework\Http\Objects\Request;
use Framework\Http\ViewEngine\ViewEngineInterface;

class CoreAbstractController extends AbstractController
{
    public AuditLogService $auditLogService;
    public function __construct(ViewEngineInterface $viewEngine, public EntityManager $entityManager)
    {
        parent::__construct($viewEngine);
        $this->auditLogService = new AuditLogService($this->entityManager);
    }

    public function getFlash(Request $request, string $key): mixed
    {
        $value = $request->session()->get("flash.{$key}");
        $request->session()->remove("flash.{$key}");
        return $value;
    }

    public function setFlash(Request $request, string $key, mixed $value): void
    {
        $request->session()->set("flash.{$key}", $value);
    }

    public function buildPaginationUrl(array $queryParameters, int $page): string
    {
        $parameters = $queryParameters;

        if($page > 1) {
            $parameters['page'] = $page;
        }

        return '/app/licenses' . (empty($parameters) ? '' : '?' . http_build_query($parameters));
    }

    public function buildPaginationPages(array $queryParameters, int $currentPage, int $totalPages): array
    {
        if($totalPages <= 1) {
            return [];
        }

        $pages = [];

        if($totalPages <= 7) {
            $pageNumbers = range(1, $totalPages);
        } else {
            $pageNumbers = [1];
            $start = max(2, $currentPage - 1);
            $end = min($totalPages - 1, $currentPage + 1);

            if($start > 2) {
                $pageNumbers[] = 'ellipsis';
            }

            for($page = $start; $page <= $end; $page++) {
                $pageNumbers[] = $page;
            }

            if($end < $totalPages - 1) {
                $pageNumbers[] = 'ellipsis';
            }

            $pageNumbers[] = $totalPages;
        }

        foreach($pageNumbers as $pageNumber) {
            if($pageNumber === 'ellipsis') {
                $pages[] = [
                    'type' => 'ellipsis',
                ];

                continue;
            }

            $pages[] = [
                'type' => 'page',
                'number' => $pageNumber,
                'url' => $this->buildPaginationUrl($queryParameters, $pageNumber),
                'active' => $pageNumber === $currentPage,
            ];
        }

        return $pages;
    }
}