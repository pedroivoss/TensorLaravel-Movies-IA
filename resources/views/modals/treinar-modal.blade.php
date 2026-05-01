{{--
    Modal: Treinar Modelo TF.js
    Aberto via: data-bs-toggle="modal" data-bs-target="#modal-treinar"
    O log e os botões usam os IDs referenciados em treinamento.js
--}}
<div class="modal fade" id="modal-treinar" tabindex="-1" aria-labelledby="modal-treinar-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 560px;">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 8px 32px rgba(0,0,0,.15);">

            {{-- Header --}}
            <div class="modal-header" style="border-bottom: 1px solid #f0f0f0; padding: 16px 24px;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-cpu-fill" style="color:#1677ff;font-size:18px;"></i>
                    <h5 class="modal-title" id="modal-treinar-label" style="margin:0;font-weight:600;font-size:16px;">
                        Treinar Modelo TF.js
                    </h5>
                    <span class="ai-tag ai-tag--orange" style="font-size:11px;">
                        <i class="bi bi-robot"></i> TensorFlow.js
                    </span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            {{-- Body --}}
            <div class="modal-body" style="padding: 24px;">

                <p style="font-size:13px;color:#00000073;margin-bottom:16px;">
                    O modelo será treinado diretamente no browser com os dados do banco.
                    Acompanhe o progresso no log abaixo.
                </p>

                <button id="btn-treinar" class="ai-btn ai-btn--primary w-100 mb-3"
                        style="height:40px;font-size:14px;">
                    <i class="bi bi-play-fill"></i>
                    <span id="btn-treinar-label">Iniciar Treinamento</span>
                </button>

                {{-- Barra de progresso (oculta até iniciar) --}}
                <div id="train-progress-wrap" class="d-none mb-3">
                    <div class="d-flex justify-content-between mb-1" style="font-size:12px;color:#00000073;">
                        <span>Progresso</span>
                        <span id="train-progress-label">0%</span>
                    </div>
                    <div class="progress" style="height:6px;border-radius:4px;">
                        <div id="train-progress-bar"
                             class="progress-bar"
                             role="progressbar"
                             style="width:0%;background:#1677ff;border-radius:4px;"
                             aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                </div>

                {{-- Log container --}}
                <div style="font-size:12px;color:#00000073;margin-bottom:6px;">
                    <i class="bi bi-terminal me-1"></i> Log de Treinamento
                </div>
                <div id="log-container"
                     style="height:180px;overflow-y:auto;font-family:'SFMono-Regular',Consolas,monospace;
                            font-size:12px;background:#141414;color:#52c41a;padding:10px 12px;
                            border-radius:6px;border:1px solid #303030;">
                    Aguardando início...
                </div>

            </div>

            {{-- Footer --}}
            <div class="modal-footer" style="border-top:1px solid #f0f0f0;padding:12px 24px;">
                <span style="font-size:12px;color:#00000073;margin-right:auto;">
                    <i class="bi bi-info-circle me-1"></i>
                    O treinamento ocorre inteiramente no browser.
                </span>
                <button type="button" class="ai-btn ai-btn--default" data-bs-dismiss="modal">
                    Fechar
                </button>
            </div>

        </div>
    </div>
</div>
