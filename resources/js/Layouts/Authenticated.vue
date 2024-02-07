<script setup>
import {Head, usePage} from '@inertiajs/vue3'
import Sidebar from '@/Components/Sidebar/Sidebar.vue'
import Navbar from '@/Components/Navbar.vue'
import PageFooter from '@/Components/PageFooter.vue'
import { sidebarState } from '@/Composables'
import ToastList from "@/Components/ToastList.vue";
import {ref} from "vue";
import {Inertia} from "@inertiajs/inertia";
import Alert from "@/Components/Alert.vue";

defineProps({
    title: String
})
const page = usePage();
const showAlert = ref(false);
const intent = ref(null);
const alertTitle = ref('');
const alertMessage = ref(null);
const alertButton = ref(null);

let removeFinishEventListener = Inertia.on("finish", () => {
    if (page.props.success) {
        showAlert.value = true
        intent.value = 'success'
        alertTitle.value = page.props.title
        alertMessage.value = page.props.success
        alertButton.value = page.props.alertButton
    } else if (page.props.warning) {
        showAlert.value = true
        intent.value = 'warning'
        alertTitle.value = page.props.title
        alertMessage.value = page.props.warning
        alertButton.value = page.props.alertButton
    }
});
</script>

<template>
    <Head :title="title"></Head>

    <div
        class="min-h-screen text-gray-900 bg-gray-100 dark:bg-gray-950 dark:text-gray-100"
    >
        <!-- Sidebar -->
        <Sidebar />

        <div
            style="transition-property: margin; transition-duration: 150ms"
            :class="[
                'min-h-screen flex flex-col',
                {
                    'lg:ml-72': sidebarState.isOpen,
                    'md:ml-16': !sidebarState.isOpen,
                },
            ]"
        >
            <!-- Navbar -->
            <Navbar />

            <!-- Page Heading -->
            <header v-if="$slots.header">
                <div class="p-4 sm:p-6">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 px-4 sm:px-6">
                <Alert
                    :show="showAlert"
                    :on-dismiss="() => showAlert = false"
                    :title="alertTitle"
                    :intent="intent"
                    :alertButton="alertButton"
                >
                    {{ alertMessage }}
                </Alert>
                <ToastList />
                <slot />
            </main>

            <PageFooter />
        </div>
    </div>
</template>
