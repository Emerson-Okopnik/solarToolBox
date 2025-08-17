<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\SeriesCalculatorService;
use App\Models\Modulo;
use App\Models\Clima;
use App\Exceptions\CompatibilityException;

class SeriesCalculatorTest extends TestCase
{
    private SeriesCalculatorService $calculator;
    private Modulo $modulo;
    private Clima $clima;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->calculator = new SeriesCalculatorService();
        
        // Criar módulo de teste
        $this->modulo = new Modulo([
            'modelo' => 'Teste 450W',
            'potencia' => 450,
            'voc' => 45.0,
            'vmp' => 37.5,
            'isc' => 12.5,
            'imp' => 12.0,
            'beta_voc' => -0.003,
            'beta_vmp' => -0.004,
            'noct' => 45
        ]);
        
        // Criar clima de teste
        $this->clima = new Clima([
            'nome' => 'Teste',
            'temperatura_minima' => -5,
            'temperatura_maxima' => 40,
            'irradiancia_media' => 1000
        ]);
    }

    public function test_calcula_serie_com_modulos_compativeis()
    {
        $modulos = [$this->modulo, $this->modulo, $this->modulo];
        $ns = 3;
        
        $resultado = $this->calculator->calcularSerie($modulos, $ns, $this->clima);
        
        $this->assertTrue($resultado['compativel']);
        $this->assertEquals(135.0, $resultado['voc_string_stc']); // 45 * 3
        $this->assertEquals(112.5, $resultado['vmp_string_stc']); // 37.5 * 3
        $this->assertEquals(12.0, $resultado['imp_string']);
        $this->assertEquals(12.5, $resultado['isc_string']);
    }

    public function test_calcula_tensao_a_frio_corretamente()
    {
        $modulos = [$this->modulo, $this->modulo];
        $ns = 2;
        
        $resultado = $this->calculator->calcularSerie($modulos, $ns, $this->clima);
        
        // Voc_frio = Voc_stc * (1 + |beta_voc| * delta_T)
        // delta_T = -5 - 25 = -30°C
        // Voc_frio = 90 * (1 + 0.003 * 30) = 90 * 1.09 = 98.1V
        $this->assertEquals(98.1, $resultado['voc_string_frio']);
    }

    public function test_rejeita_modulos_incompativeis()
    {
        $modulo2 = new Modulo([
            'modelo' => 'Teste 500W',
            'potencia' => 500,
            'voc' => 50.0, // Diferença > 5%
            'vmp' => 40.0,
            'isc' => 13.0,
            'imp' => 12.5,
            'beta_voc' => -0.003,
            'beta_vmp' => -0.004,
            'noct' => 45
        ]);
        
        $modulos = [$this->modulo, $modulo2];
        
        $this->expectException(CompatibilityException::class);
        $this->calculator->calcularSerie($modulos, 2, $this->clima);
    }

    public function test_valida_limite_tensao_dc_max()
    {
        $modulos = array_fill(0, 25, $this->modulo); // 25 módulos
        $vdc_max = 1000; // Limite do inversor
        
        $resultado = $this->calculator->calcularSerie($modulos, 25, $this->clima, $vdc_max);
        
        // Voc_frio seria muito alta para 25 módulos
        $this->assertFalse($resultado['dentro_limite_vdc']);
    }
}
