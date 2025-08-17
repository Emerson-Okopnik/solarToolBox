<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Projeto;
use App\Models\Clima;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProjetoApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Clima $clima;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->clima = Clima::factory()->create();
    }

    public function test_pode_listar_projetos()
    {
        Projeto::factory()->count(3)->create(['user_id' => $this->user->id]);
        
        $response = $this->actingAs($this->user)
            ->getJson('/api/projetos');
        
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_pode_criar_projeto()
    {
        $dados = [
            'nome' => 'Projeto Teste',
            'cliente' => 'Cliente Teste',
            'clima_id' => $this->clima->id
        ];
        
        $response = $this->actingAs($this->user)
            ->postJson('/api/projetos', $dados);
        
        $response->assertStatus(201)
            ->assertJsonFragment(['nome' => 'Projeto Teste']);
        
        $this->assertDatabaseHas('projetos', $dados);
    }

    public function test_pode_buscar_projeto_especifico()
    {
        $projeto = Projeto::factory()->create(['user_id' => $this->user->id]);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/projetos/{$projeto->id}");
        
        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $projeto->id]);
    }

    public function test_nao_pode_acessar_projeto_de_outro_usuario()
    {
        $outroUser = User::factory()->create();
        $projeto = Projeto::factory()->create(['user_id' => $outroUser->id]);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/projetos/{$projeto->id}");
        
        $response->assertStatus(404);
    }

    public function test_validacao_campos_obrigatorios()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/projetos', []);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nome', 'cliente', 'clima_id']);
    }
}
