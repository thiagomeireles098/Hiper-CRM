module.exports = {
    appId: 'br.com.hiperlinksolutions.hipercaixa',
    productName: 'HiperCaixa',
    copyright: 'Copyright © Hiperlink Solutions',
    directories: {
        app: 'electron',
        output: 'public/hipercaixa/build',
    },
    files: [
        '**/*',
    ],
    win: {
        signAndEditExecutable: false,
        target: [
            {
                target: 'portable',
                arch: ['x64'],
            },
        ],
        artifactName: 'HiperCaixa.exe',
    },
};
