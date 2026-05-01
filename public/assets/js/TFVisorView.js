const visorState = {
    lossPoints: [],
    accPoints: []
};

function resetTrainingVisor() {
    visorState.lossPoints = [];
    visorState.accPoints = [];

    const container = document.getElementById('visor-container');
    container.innerHTML = `
        <div id="chart-accuracy" style="height:300px; margin-bottom:16px;"></div>
        <div id="chart-loss" style="height:300px;"></div>
    `;
}

function updateTrainingVisor({ epoch, loss, accuracy }) {
    visorState.lossPoints.push({ x: epoch, y: loss });
    visorState.accPoints.push({ x: epoch, y: accuracy });

    tfvis.render.linechart(
        document.getElementById('chart-accuracy'),
        {
            values: [visorState.accPoints],
            series: ['Precisão']
        },
        {
            xLabel: 'Época',
            yLabel: 'Precisão',
            height: 260
        }
    );

    tfvis.render.linechart(
        document.getElementById('chart-loss'),
        {
            values: [visorState.lossPoints],
            series: ['Erro']
        },
        {
            xLabel: 'Época',
            yLabel: 'Loss',
            height: 260
        }
    );
}
