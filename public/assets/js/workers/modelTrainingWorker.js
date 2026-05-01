async function treinarModelo() {

    let _globalCtx = {}
    let _model = null

    const WEIGHTS = {
        category: 0.4,
        price: 0.2,
        age: 0.1
    }

    //pega dados dos usuarios
    const

    return new Promise((resolve) => {
        $('#btn-treinar').prop('disabled', true).text('Treinando...');
        console.log('Iniciando criação do modelo...');

        $('#btn-treinar').text('Treinado!');
        $('#btn-prever').prop('disabled', false);

        resolve(); // Avisa o await que pode continuar
    });
}
