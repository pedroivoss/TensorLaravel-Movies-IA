importScripts('https://cdn.jsdelivr.net/npm/@tensorflow/tfjs');

let _globalCtx = {};

let _model = null

//Normaliza um valor entre 0 e 1, para evitar que a rede precise lidar com escalas diferentes
//exemplo de idade entre 18 e 65, ou preço entre 10 e 1000
//entao a idade 18 vira 0, a idade 65 vira 1, e a idade 40 vira algo em torno de 0.4

const normalize = (value, min, max) => (value - min) / (max - min) || 1

const makeContext = (movies, users) => {

}

async function trainModel() {
    return new Promise((resolve) => {
        console.log('Training model with users:', app.users, 'and movies:', app.movies);



        resolve(); // Avisa o await que pode continuar
    });
}
