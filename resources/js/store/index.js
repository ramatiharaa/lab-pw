import Vue from "vue";
import Vuex from "vuex";
import axios from "axios";
import router from "./../router";

Vue.use(Vuex);

export default new Vuex.Store({
    state: {
        user: JSON.parse(localStorage.getItem("user")) || {},
        startPage: false,
    },
    getters: {
        isLoggedIn(state) {
            return !!state.user && !!state.user.id;
        },
    },
    mutations: {
        userData(state, user) {
            state.user = user;
            localStorage.setItem("token", user.api_token);
            localStorage.setItem("user", JSON.stringify(user));
            axios.defaults.headers.common["Authorization"] =
                "Bearer " + localStorage.getItem("token");
        },
        getUserData(state) {
            axios.get("/api/user").then(({ data }) => {
                state.startPage = true;
                this.commit("userData", data);
            });
        },
        startPageStatus(state, status) {
            state.startPage = status;
        },
        logout(state) {
            axios.get("/api/auth/logout").then(() => {
                state.user = "";
                localStorage.removeItem("token");
                localStorage.removeItem("user");
                router.push("/login");
                axios.defaults.headers.common["Authorization"] = "";
            });
        },
    },
    actions: {
        logout(context) {
            context.commit("logout");
        },
    },
});
