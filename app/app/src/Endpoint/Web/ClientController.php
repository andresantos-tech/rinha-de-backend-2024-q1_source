<?php

declare(strict_types=1);

namespace App\Endpoint\Web;

use App\Endpoint\Web\Filter\TransactionFilter;
use App\Exception\InsufficientAmountException;
use App\Exception\NotFoundException;
use App\Service\ClientService;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Spiral\Router\Annotation\Route;

class ClientController
{
    #[Route(route: '/clientes/<accountId:int>/transacoes', name: 'create-transaction', methods: 'POST')]
    public function createTransaction(
        TransactionFilter $filter,
        ClientService $service,
        string $accountId
    ): ResponseInterface
    {
        $accountId = (int) $accountId;

        try {
            $result = $service->createTransaction($accountId, $filter);
        } catch (NotFoundException $e) {
            return new Response(404, ['content-type' => 'application/json']);
        } catch (InsufficientAmountException $e) {
            return new Response(422, ['content-type' => 'application/json']);
        }

        $response = new Response(200, ['content-type' => 'application/json']);
        $response->getBody()->write(json_encode(
            [
                'limite' => $result['limit_amount'],
                'saldo' => $result['current_balance'],
            ]
        ));

        return $response;
    }

    #[Route('/clientes/<accountId:int>/extrato', name: 'show-statement', methods: 'GET')]
    public function showStatement(
        ClientService $service,
        string $accountId
    ): ResponseInterface
    {
        $accountId = (int) $accountId;

        if ( !$account = $service->getAccount($accountId)) {
            return new Response(404, ['content-type' => 'application/json']);
        }

        $response = new Response(200, ['content-type' => 'application/json']);
        $response->getBody()->write(json_encode(
            [
                'saldo' => [
                    'total' => $account['current_balance'],
                    'data_extrato' => date('c'),
                    'limite' => $account['limit_amount'],
                ],
                'ultimas_transacoes' => $service->getRecentTransactions($accountId),
            ]
        ));

        return $response;
    }
}
