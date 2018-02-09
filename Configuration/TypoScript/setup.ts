
# Module configuration
module.tx_userimport_web_userimportuserimport {
    persistence {
        storagePid = {$module.tx_userimport_userimport.persistence.storagePid}
    }
    view {
        templateRootPaths.0 = EXT:userimport/Resources/Private/Backend/Templates/
        templateRootPaths.1 = {$module.tx_userimport_userimport.view.templateRootPath}
        partialRootPaths.0 = EXT:userimport/Resources/Private/Backend/Partials/
        partialRootPaths.1 = {$module.tx_userimport_userimport.view.partialRootPath}
        layoutRootPaths.0 = EXT:userimport/Resources/Private/Backend/Layouts/
        layoutRootPaths.1 = {$module.tx_userimport_userimport.view.layoutRootPath}
    }
}
