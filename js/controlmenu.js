/* al cargar dom */
document.addEventListener("DOMContentLoaded", async () => {
    const btnMenu = document.getElementById("customSidebarToggle");
    const menu = document.getElementById("sidebarMenu");
    let menuCollapsed = "open";

    if (localStorage.getItem('sidebarState')) {
        menuCollapsed = localStorage.getItem('sidebarState');
    }

    if (menuCollapsed === "closed") {
        menu.classList.add("collapsed");
    } else {
        menu.classList.remove("collapsed");
    }

    /* btnMenu.addEventListener("click", (e) => {
        e.preventDefault();

        
        if (menu.classList.contains("collapsed")) {
            menuCollapsed = "cerrado";
        }else{
            menuCollapsed = "abierto";
        }
        localStorage.setItem('menu', menuCollapsed);
    }); */

});
