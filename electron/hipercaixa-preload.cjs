const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('hiperCaixaDesktop', {
    version: () => ipcRenderer.invoke('hipercaixa:get-app-version'),
    openExternal: (url) => ipcRenderer.invoke('hipercaixa:open-external', url),
    isDesktop: true,
});
