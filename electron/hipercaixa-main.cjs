const { app, BrowserWindow, Menu, ipcMain, shell } = require('electron');
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
