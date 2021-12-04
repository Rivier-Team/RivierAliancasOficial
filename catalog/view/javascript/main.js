/*=============== SHOW MENU ===============*/
const navMenu = document.getElementById('nav-menu'),
    navToggle = document.getElementById('nav-toggle'),
    navClose = document.getElementById('nav-close')

/*===== MENU SHOW =====*/
/* Validate if constant exists */
if (navToggle) {
    navToggle.addEventListener('click', () => {
        navMenu.classList.add('show-menu')
    })
}

/*===== MENU HIDDEN =====*/
/* Validate if constant exists */
if (navClose) {
    navClose.addEventListener('click', () => {
        navMenu.classList.remove('show-menu')
    })
}

/*=============== REMOVE MENU MOBILE ===============*/
const navLink = document.querySelectorAll('.nav__link')

function linkAction() {
    const navMenu = document.getElementById('nav-menu')
    // When we click on each nav__link, we remove the show-menu class
    navMenu.classList.remove('show-menu')
}
navLink.forEach(n => n.addEventListener('click', linkAction))

/*=============== CHANGE BACKGROUND HEADER ===============*/
function scrollHeader() {
    const logoHeader = document.getElementById('logo')
    const header = document.getElementById('header')
    // When the scroll is greater than 50 viewport height, add the scroll-header class to the header tag
    if (this.scrollY >= 50) {
        header.classList.add('scroll-header');
        logoHeader.classList.add('logoheader');

    } else { header.classList.remove('scroll-header'); logoHeader.classList.remove('logoheader'); }


}
window.addEventListener('scroll', scrollHeader)

/*=============== TESTIMONIAL SWIPER ===============*/
let testimonialSwiper = new Swiper(".testimonial-swiper", {
    spaceBetween: 30,
    loop: 'true',

    navigation: {
        nextEl: ".swiper-button-next",
        prevEl: ".swiper-button-prev",
    },
});

/*=============== NEW SWIPER ===============*/
let newSwiper = new Swiper(".new-swiper", {
    spaceBetween: 24,
    loop: 'true',

    breakpoints: {
        576: {
            slidesPerView: 2,
        },
        768: {
            slidesPerView: 3,
        },
        1024: {
            slidesPerView: 4,
        },
    },
});

/*=============== SCROLL SECTIONS ACTIVE LINK ===============*/
const sections = document.querySelectorAll('section[id]')

function scrollActive() {
    const scrollY = window.pageYOffset

    sections.forEach(current => {
        const sectionHeight = current.offsetHeight,
            sectionTop = current.offsetTop - 58,
            sectionId = current.getAttribute('id')

        if (scrollY > sectionTop && scrollY <= sectionTop + sectionHeight) {
            document.querySelector('.nav__menu a[href*=' + sectionId + ']').classList.add('active-link')
        } else {
            document.querySelector('.nav__menu a[href*=' + sectionId + ']').classList.remove('active-link')
        }
    })
}
window.addEventListener('scroll', scrollActive)

/*=============== SHOW SCROLL UP ===============*/
function scrollUp() {
    const scrollUp = document.getElementById('scroll-up');
    // When the scroll is higher than 350 viewport height, add the show-scroll class to the a tag with the scroll-top class
    if (this.scrollY >= 350) scrollUp.classList.add('show-scroll'); else scrollUp.classList.remove('show-scroll')
}
window.addEventListener('scroll', scrollUp)

/*=============== SHOW CART ===============*/
const cartProduto = document.getElementById('cart'),
    cartShop = document.getElementById('cart-shop'),
    cartClose = document.getElementById('cart-close')

/*===== CART SHOW =====*/
/* Validate if constant exists */
if (cartShop) {
    cartShop.addEventListener('click', () => {
        // cart.classList.add('show-cart')
    })
}


/*===== CART HIDDEN =====*/
/* Validate if constant exists */
if (cartClose) {
    cartClose.addEventListener('click', () => {
        cartProduto.classList.remove('show-cart')
    })
}

/*=============== DARK LIGHT THEME ===============*/
const darkTheme = 'dark-theme'
    , iconmoon = document.querySelector('.moon')
    , iconsunny = document.querySelector('.sunny')

iconmoon.addEventListener('click', () => {
    document.body.classList.toggle(darkTheme)
    iconsunny.classList.add('ativo')
    iconmoon.classList.remove('ativo')
})
iconsunny.addEventListener('click', () => {
    document.body.classList.toggle(darkTheme)
    iconsunny.classList.remove('ativo')
    iconmoon.classList.add('ativo')
})

/*=============== SIDEBAR MATERIAL JELLY ===============*/

function cartSideBar() {
    $('.outside').toggleClass('in');
    $('.bar').toggleClass('active');
    $('.cart__container').toggleClass('ativo');
    $(this).toggleClass('is-showing');
}

$("#cart-shop").click(cartSideBar);
$("#cart-close").click(cartSideBar);


/*=============== SHOW MODAL ===============*/
const showModal = (openButton, modalContent) => {
    const openBtn = document.getElementById(openButton),
        modalContainer = document.getElementById(modalContent)

    if (openBtn && modalContainer) {
        openBtn.addEventListener('click', () => {
            modalContainer.classList.add('show-modal')
        })
    }
}
showModal('open-modal', 'modal-container')

/*=============== CLOSE MODAL ===============*/
const closeBtn = document.querySelectorAll('.close-modal')

function closeModal() {
    const modalContainer = document.getElementById('modal-container')
    modalContainer.classList.remove('show-modal')
}
closeBtn.forEach(c => c.addEventListener('click', closeModal))




/*=============== SEARCH BOX ===============*/
const searchBox = document.querySelector(".search-box");
const searchBtn = document.querySelector(".search-icon");
const cancelBtn = document.querySelector(".cancel-icon");
const searchInput = document.querySelector(".inputSearch");
const searchData = document.querySelector(".search-data");
searchBtn.onclick = () => {
    searchBox.classList.add("active");
    searchBtn.classList.add("active");
    searchInput.classList.add("active");
    cancelBtn.classList.add("active");
    searchInput.focus();
    if (searchInput.value != "") {
        var values = searchInput.value;
        searchData.classList.remove("active");
        searchData.innerHTML = "Você digitou: " + "<span style='font-weight: 500;'>" + values + "</span> aperte 'Enter' para continuar";
    } else {
        searchData.textContent = "";
    }
}
cancelBtn.onclick = () => {
    searchBox.classList.remove("active");
    searchBtn.classList.remove("active");
    searchInput.classList.remove("active");
    cancelBtn.classList.remove("active");
    searchData.classList.toggle("active");
    searchInput.value = "";
}


/*=============== PRELOADER ===============*/
setTimeout(function () {
    $("html").addClass("loader");
    $("html").removeClass("loader");
    $(".loader").css({ display: "none" });
}, 3500);
