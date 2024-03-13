<script setup>
import {Head, Link, usePage} from '@inertiajs/vue3'
import { MoonIcon, SunIcon } from '@heroicons/vue/outline'
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import PageFooter from '@/Components/PageFooter.vue'
import Button from '@/Components/Button.vue'
import { toggleDarkMode, isDark } from '@/Composables'
import ToastList from "@/Components/ToastList.vue";
import Alert from "@/Components/Alert.vue";
import {ref} from "vue";
import {Inertia} from "@inertiajs/inertia";
import DropdownLink from "@/Components/DropdownLink.vue";
import {TranslateIcon} from "@/Components/Icons/outline.jsx";
import Dropdown from "@/Components/Dropdown.vue";
import {loadLanguageAsync} from "laravel-vue-i18n";

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

const changeLanguage = async (langVal) => {
    try {
        await loadLanguageAsync(langVal);
        await axios.get(`/locale/${langVal}`);
    } catch (error) {
        console.error('Error changing locale:', error);
    }
};
</script>

<template>
    <Head :title="title" />

    <div
        class="flex flex-col items-center justify-center min-h-screen gap-4 py-6 bg-gray-100 dark:bg-dark-eval-0"
    >

        <div class="flex items-center gap-2 self-stretch w-full justify-end pt-2 pr-2 sm:pr-8">
            <Dropdown align="right">
                <template #trigger>
                    <Button
                        iconOnly
                        variant="secondary"
                        type="button"
                        v-slot="{ iconSizeClasses }"
                        class="inline-flex"
                        srText="Toggle dark mode"
                    >
                        <TranslateIcon
                            aria-hidden="true"
                            :class="iconSizeClasses"
                        />
                    </Button>
                </template>
                <template #content>
                    <DropdownLink @click="changeLanguage('en')">
                        <div class="inline-flex items-center gap-2">
                            English
                        </div>
                    </DropdownLink>
                    <DropdownLink @click="changeLanguage('cn')">
                        <div class="inline-flex items-center gap-2">
                            中文
                        </div>
                    </DropdownLink>
                </template>
            </Dropdown>
            <Button
                iconOnly
                variant="secondary"
                type="button"
                @click="() => { toggleDarkMode() }"
                v-slot="{ iconSizeClasses }"
                class="inline-flex"
                srText="Toggle dark mode"
            >
                <MoonIcon
                    v-show="!isDark"
                    aria-hidden="true"
                    :class="iconSizeClasses"
                />
                <SunIcon
                    v-show="isDark"
                    aria-hidden="true"
                    :class="iconSizeClasses"
                />
            </Button>
        </div>
        <main class="flex items-center flex-1 w-full sm:max-w-2xl">
            <div
                class="w-full px-6 py-4 bg-white shadow-md sm:rounded-lg dark:bg-dark-eval-1"
            >
                <div class="flex justify-center items-center">
                    <ApplicationLogo aria-hidden="true" class="w-20 h-20" />
                    <div
                        class="text-lg font-bold text-gray-800 dark:text-white"
                    >
                        LuckyAnt Trading
                    </div>
                </div>
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
            </div>
        </main>

        <PageFooter />

    </div>
</template>
