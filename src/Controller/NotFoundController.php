<?php
declare(strict_types=1);
namespace App\Controller;

use Framework\Http\Interfaces\NotFoundControllerInterface;
use Framework\Http\Objects\Request;
use Framework\Http\Objects\Response;

class NotFoundController extends CoreAbstractController implements NotFoundControllerInterface
{
    public function handle(Request $request): Response
    {
        return $this->redirect("/login");
    }
}