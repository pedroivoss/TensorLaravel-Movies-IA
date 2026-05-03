<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiModel extends Model
{
    /**
     * O nome da tabela associada ao modelo.
     * (Opcional se você seguiu o padrão de nomes do Laravel)
     */
    protected $table = 'ai_models';

    /**
     * Os atributos que podem ser preenchidos em massa.
     *
     * topology: Armazena a estrutura JSON da rede neural.
     * weights_base64: Armazena os pesos binários codificados em Base64.
     */
    protected $fillable = [
        'name',
        'model_topology',
        'weights_base64',
    ];

    protected $casts = [
        'model_topology' => 'array',
    ];

    /**
     * Caso você queira garantir que o modelo seja sempre tratado como
     * um objeto limpo, você pode desativar o incremento se não for usar IDs.
     * Mas para esse projeto, manter o padrão é o ideal.
     */
}
