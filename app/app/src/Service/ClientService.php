<?php

namespace App\Service;

use App\Endpoint\Web\Filter\TransactionFilter;
use App\Exception\InsufficientAmountException;
use App\Exception\NotFoundException;
use Cycle\Database\Database;
use Cycle\Database\StatementInterface;
use Spiral\Core\Attribute\Singleton;

#[Singleton]
readonly class ClientService
{
    public function __construct(
        private Database $db
    )
    {

    }

    /**
     * @param int $accountId
     * @return array|null
     */
    public function getAccount(int $accountId):? array
    {
        $account = $this->db->query('SELECT * FROM accounts WHERE id = ?', [
            $accountId
        ])->fetch();

        return $account ?: null;
    }

    /**
     * @param array $account
     * @param TransactionFilter $filter
     * @return void
     * @throws InsufficientAmountException
     */
    public function canCreateTransaction(array $account, TransactionFilter $filter): void
    {
        $invalid = $filter->tipo === 'd' &&
            $account['limit_amount'] + $account['current_balance'] < $filter->valor;

        if ($invalid) {
            throw new InsufficientAmountException();
        }
    }

    /**
     * @param int $accountId
     * @param TransactionFilter $filter
     * @return array
     * @throws \Throwable
     * @throws NotFoundException
     * @throws InsufficientAmountException
     */
    public function createTransaction(int $accountId, TransactionFilter $filter): array
    {
        $limit = null;
        $newBalance = null;

        $this->db->transaction(function (Database $db) use ($accountId, $filter, &$limit, &$newBalance) {
            $account = $this->db->query('SELECT * FROM accounts WHERE id = ? FOR UPDATE', [
                $accountId
            ])->fetch();

            if (!$account) {
                throw new NotFoundException();
            }

            $this->canCreateTransaction($account, $filter);

            $db->insert('transactions')
                ->columns([
                    'account_id',
                    'amount',
                    'transaction_type',
                    'description',
                ])
                ->values([
                    $account['id'],
                    $filter->valor,
                    $filter->tipo,
                    $filter->descricao
                ])
                ->run();

            $limit = $account['limit_amount'];
            $newBalance = $account['current_balance'] + ($filter->tipo === 'd' ? -$filter->valor : $filter->valor);

            $db->execute(
                'UPDATE accounts SET current_balance = ? WHERE id = ?',
                [
                    $newBalance,
                    $account['id'],
                ]
            );
        }, Database::ISOLATION_READ_COMMITTED);

        return [
            'limit_amount' => $limit,
            'current_balance' => $newBalance,
        ];
    }

    /**
     * @param int $accountId
     * @param int $limit
     * @return array
     */
    public function getRecentTransactions(int $accountId, int $limit = 10): array
    {
        return $this->db->query('
            SELECT
                amount AS valor,
                transaction_type AS tipo,
                description AS descricao,
                date AS realizada_em
            FROM transactions
            WHERE account_id = ?
            ORDER BY id DESC
            LIMIT ?
        ', [
            $accountId,
            $limit
        ])->fetchAll();
    }
}
