
module.tx_userimport_userimport {
    view {
        # cat=module.tx_userimport_userimport/file; type=string; label=Path to template root (BE)
        templateRootPath = EXT:userimport/Resources/Private/Backend/Templates/
        # cat=module.tx_userimport_userimport/file; type=string; label=Path to template partials (BE)
        partialRootPath = EXT:userimport/Resources/Private/Backend/Partials/
        # cat=module.tx_userimport_userimport/file; type=string; label=Path to template layouts (BE)
        layoutRootPath = EXT:userimport/Resources/Private/Backend/Layouts/
    }
    persistence {
        # cat=module.tx_userimport_userimport//a; type=string; label=Default storage PID
        storagePid =
    }
}
