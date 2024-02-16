<?php

namespace Tests\Feature;

use Tests\TestCase;

use Spiral\DatabaseSeeder\Database\Traits\{
    DatabaseAsserts, Helper, DatabaseFromSQL, ShowQueries
};

final class CreateTransactionTest extends TestCase
{
    use DatabaseFromSQL,
        Helper,
        DatabaseAsserts,
        ShowQueries;

    protected function getPrepareSQLFilePath(): string
    {
        return __DIR__ . '/refresh_db.sql';
    }

    protected function getDropSQLFilePath(): string
    {
        return __DIR__ . '/refresh_db.sql';
    }

    public function testAccountNotFound()
    {
        $http = $this->fakeHttp();
        $response = $http->post('/clientes/6/transacoes', [
            'valor' => 1000,
            'tipo' => 'c',
            'descricao' => 'descricao'
        ]);
        $response->assertNotFound();
    }

    public function testCreateTransactionCredit()
    {
        $http = $this->fakeHttp();
        $response = $http->post('/clientes/1/transacoes', [
            'valor' => 1250,
            'tipo' => 'c',
            'descricao' => 'descricao'
        ]);
        $response->assertStatus(200);
        $response->assertBodySame(json_encode(
            [
                'limite' => 100000,
                'saldo' => 1250
            ]
        ));
        $this->tearDownDatabaseFromSQL();
    }

    public function testCreateTransactionDebit()
    {
        $http = $this->fakeHttp();
        $response = $http->post('/clientes/1/transacoes', [
            'valor' => 50000,
            'tipo' => 'd',
            'descricao' => 'descricao'
        ]);
        $response->assertStatus(200);
        $response->assertBodySame(json_encode(
            [
                'limite' => 100000,
                'saldo' => -50000
            ]
        ));
        $this->tearDownDatabaseFromSQL();
    }

    public function testCreateTransactionDebitInvalid()
    {
        $http = $this->fakeHttp();
        $response = $http->post('/clientes/1/transacoes', [
            'valor' => 1000000,
            'tipo' => 'd',
            'descricao' => 'descricao'
        ]);
        $response->assertStatus(422);
        $this->tearDownDatabaseFromSQL();
    }

    public function testMultipleTransactions()
    {
        $http = $this->fakeHttp();
        $http->post('/clientes/1/transacoes', [
            'valor' => 1250,
            'tipo' => 'd',
            'descricao' => 'descricao'
        ]);
        $http->post('/clientes/1/transacoes', [
            'valor' => 1250,
            'tipo' => 'd',
            'descricao' => 'descricao'
        ]);
        $http->post('/clientes/1/transacoes', [
            'valor' => 1250,
            'tipo' => 'd',
            'descricao' => 'descricao'
        ]);
        $response = $http->post('/clientes/1/transacoes', [
            'valor' => 3749,
            'tipo' => 'c',
            'descricao' => 'descricao'
        ]);
        $response->assertStatus(200);
        $response->assertBodySame(json_encode(
            [
                'limite' => 100000,
                'saldo' => -1
            ]
        ));
        $this->tearDownDatabaseFromSQL();
    }
}
