const { app, BrowserWindow, Menu, ipcMain, shell, dialog } = require('electron');
const fs = require('fs');
const https = require('https');
const path = require('path');

const isDev = !app.isPackaged;

function createWindow() {
    const window = new BrowserWindow({
        width: 1366,
        height: 768,
        minWidth: 1024,
        minHeight: 680,
        title: 'HiperCaixa',
        autoHideMenuBar: true,
        backgroundColor: '#dff8fb',
        webPreferences: {
            preload: path.join(__dirname, 'hipercaixa-preload.cjs'),
            contextIsolation: true,
            nodeIntegration: false,
        },
    });

    Menu.setApplicationMenu(null);
    window.loadFile(path.join(__dirname, 'HiperCaixa.html'));

    if (isDev) {
        window.webContents.openDevTools({ mode: 'detach' });
    }
}

app.whenReady().then(() => {
    createWindow();

    app.on('activate', () => {
        if (BrowserWindow.getAllWindows().length === 0) {
            createWindow();
        }
    });
});

app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') {
        app.quit();
    }
});

ipcMain.handle('hipercaixa:get-app-version', () => app.getVersion());
ipcMain.handle('hipercaixa:open-external', async (_event, url) => {
    if (typeof url === 'string' && /^https?:\/\//i.test(url)) {
        await shell.openExternal(url);
    }
});

ipcMain.handle('hipercaixa:download-update', async (_event, url) => {
    if (typeof url !== 'string' || !/^https?:\/\//i.test(url)) {
        throw new Error('URL de atualizacao invalida.');
    }

    const targetPath = path.join(app.getPath('downloads'), 'HiperCaixa-atualizacao.exe');
    await new Promise((resolve, reject) => {
        const file = fs.createWriteStream(targetPath);
        https.get(url, (response) => {
            if (response.statusCode && response.statusCode >= 400) {
                reject(new Error(`Download retornou HTTP ${response.statusCode}`));
                return;
            }
            response.pipe(file);
            file.on('finish', () => {
                file.close(resolve);
            });
        }).on('error', reject);
    });

    await dialog.showMessageBox({
        type: 'info',
        title: 'Atualizacao baixada',
        message: 'A nova versao foi baixada. O HiperCaixa vai abrir o atualizador agora.',
    });
    await shell.openPath(targetPath);
    app.quit();

    return targetPath;
});

ipcMain.handle('hipercaixa:print-html', async (_event, html) => {
    if (typeof html !== 'string' || html.trim() === '') {
        throw new Error('Conteudo de impressao invalido.');
    }

    const printWindow = new BrowserWindow({
        width: 420,
        height: 720,
        show: false,
        autoHideMenuBar: true,
        webPreferences: {
            contextIsolation: true,
            nodeIntegration: false,
        },
    });

    try {
        await printWindow.loadURL(`data:text/html;charset=utf-8,${encodeURIComponent(html)}`);
        await new Promise((resolve, reject) => {
            printWindow.webContents.print({ silent: false, printBackground: true }, (success, failureReason) => {
                if (!success) {
                    reject(new Error(failureReason || 'Impressao cancelada.'));
                    return;
                }

                resolve();
            });
        });
    } finally {
        if (!printWindow.isDestroyed()) {
            printWindow.close();
        }
    }

    return true;
});
