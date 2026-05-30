const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('hiperCaixaDesktop', {
    version: () => ipcRenderer.invoke('hipercaixa:get-app-version'),
    openExternal: (url) => ipcRenderer.invoke('hipercaixa:open-external', url),
    downloadUpdate: (url) => ipcRenderer.invoke('hipercaixa:download-update', url),
    isDesktop: true,
});
