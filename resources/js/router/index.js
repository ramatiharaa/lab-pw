import Vue from "vue";
import VueRouter from "vue-router";

import Home from "./../screens/Home";
import ItemView from "./../screens/items/View";

import Login from "./../screens/auth/Login";
import NotFound from "./../screens/NotFound";

import LelangSaya from "./../screens/LelangSaya.vue";
import ListLelang from "../screens/ListLelang.vue";

Vue.use(VueRouter);

const routes = [
    {
        path: "/",
        component: Home,
    },
    {
        path: "/home",
        name: "Home",
        component: Home,
    },
    {
        path: "/items/:id",
        name: "ItemView",
        component: ItemView,
    },
    {
        path: "/lelang-saya",
        name: "LelangSaya",
        component: LelangSaya,
    },
    {
        path: "/list-lelang",
        name: "ListLelang",
        component: ListLelang,
    },
    {
        path: "/login",
        name: "Login",
        component: Login,
        meta: { forvisitors: true },
    },

    // Not Found Page
    { path: "/404", alias: "*", name: "NotFound", component: NotFound },
];

const router = new VueRouter({
    mode: "history",
    base: process.env.BASE_URL,
    routes,
});

router.beforeEach((to, from, next) => {
    var loggedIn = localStorage.getItem("token");
    // if user authianticated
    if (to.matched.some((record) => record.meta.forAuth) && !loggedIn) {
        next("/login");
    } else {
        next();
    }
    // if user dosen't authianticated
    if (to.matched.some((record) => record.meta.forvisitors) && loggedIn) {
        next("/");
    } else {
        next();
    }
});

export default router;
