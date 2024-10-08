<script setup>
import Button from "@/Components/Button.vue";
import {PlusCircleIcon} from "@heroicons/vue/solid";
import {ref} from "vue";
import Modal from "@/Components/Modal.vue";
import {Tab, TabGroup, TabList, TabPanel, TabPanels} from "@headlessui/vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import InputError from "@/Components/InputError.vue";
import Checkbox from "@/Components/Checkbox.vue";
import Label from "@/Components/Label.vue";
import {useForm} from "@inertiajs/vue3";

const props = defineProps({
    activeAccountCounts: Number,
    liveAccountQuota: Number,
    leverageSel: Array
})

const visible = ref(false);
const activeTab = ref('trading_account');

const form = useForm({
    leverage: 500,
    terms: '',
    type: 1
})

const handleTabChange = (tab) => {
    activeTab.value = tab
    form.type = tab === 'trading_account' ? 1 : 2
}

const submit = () => {
    form.post(route('account_info.createAccount'), {
        onSuccess: () => {
            closeModal()
            form.reset()
        },
        onError: (errors) => {
            console.error('Submission errors:', errors)
        }
    })
}

const closeModal = () => {
    visible.value = false
}
</script>

<template>
    <Button
        type="button"
        variant="primary"
        size="sm"
        class="flex justify-center items-center gap-2 w-full sm:w-auto"
        v-slot="{ iconSizeClasses }"
        @click="visible = true"
        :disabled="activeAccountCounts >= liveAccountQuota"
    >
        <PlusCircleIcon aria-hidden="true" :class="iconSizeClasses"/>
        <span>{{ $t('public.add_account') }}</span>
    </Button>

    <Modal
        :show="visible"
        :title="$t('public.add_account')"
        @close="closeModal"
    >
        <form class="space-y-4">
            <TabGroup>
                <TabList class="flex space-x-1 rounded-xl bg-blue-900/20 dark:bg-gray-800 p-1 w-full">
                    <Tab
                        as="template"
                        v-slot="{ selected }"
                        v-if="activeAccountCounts < liveAccountQuota"
                    >
                        <button
                            :class="[
                                    'w-full rounded-lg py-2.5 text-sm font-medium leading-5',
                                    'ring-white/60 dark:ring-primary-800 ring-offset-2 ring-offset-primary-200 dark:ring-offset-primary-800 focus:outline-none focus:ring-2',
                                    selected
                                    ? 'bg-white dark:bg-primary-900 text-primary-800 dark:text-white shadow'
                                    : 'text-blue-25 hover:bg-white/[0.12] hover:text-white',
                                ]"
                            @click="handleTabChange('trading_account')"
                        >
                            {{ $t('public.trading_account') }}
                        </button>
                    </Tab>

                    <Tab
                        as="template"
                        v-slot="{ selected }"
                        v-if="props.virtualStatus"
                    >
                        <button
                            :class="[
                                    'w-full rounded-lg py-2.5 text-sm font-medium leading-5',
                                    'ring-white/60 ring-offset-2 ring-offset-primary-200 focus:outline-none focus:ring-2',
                                    selected
                                    ? 'bg-white text-primary-800 shadow'
                                    : 'text-blue-25 hover:bg-white/[0.12] hover:text-white',
                                ]"
                            @click="handleTabChange('virtual_account')"
                        >
                            {{ $t('public.virtual_account') }}
                        </button>
                    </Tab>
                </TabList>
                <TabPanels>
                    <TabPanel class="py-3" v-if="activeAccountCounts < liveAccountQuota">
                        <div class="space-y-2">
                            <Label
                                for="leverage"
                                :value="$t('public.leverage')"
                            />
                            <BaseListbox
                                :options="leverageSel"
                                v-model="form.leverage"
                            />
                            <InputError :message="form.errors.leverage"/>
                        </div>
                        <div class="mt-6 space-y-4">
                            <h3 class="text-gray-400 dark:text-gray-300 font-bold text-sm">{{
                                    $t('public.terms_and_conditions')
                                }}</h3>
                            <ol class="text-gray-500 dark:text-gray-400 text-xs list-decimal text-justify pl-6 mt-2">
                                <li>{{ $t('public.terms_1') }}</li>
                                <li>{{ $t('public.terms_2') }}</li>
                                <li>{{ $t('public.terms_3') }}</li>
                                <li>{{ $t('public.terms_4') }}</li>
                                <li>{{ $t('public.terms_5') }}</li>
                                <li>{{ $t('public.terms_6') }}</li>
                                <li>{{ $t('public.terms_7') }}</li>
                                <li>{{ $t('public.terms_8') }}</li>
                                <li>{{ $t('public.terms_9') }}</li>
                            </ol>

                            <div class="flex items-center">
                                <div class="flex items-center h-5">
                                    <Checkbox id="terms" v-model="form.terms"/>
                                </div>
                                <div class="ml-3">
                                    <label for="terms" class="text-gray-500 dark:text-gray-400 text-xs">{{
                                            $t('public.accept_terms')
                                        }}</label>
                                </div>
                            </div>
                            <InputError :message="form.errors.terms"/>

                        </div>
                    </TabPanel>

                    <TabPanel class="py-3" v-if="props.virtualStatus">
                        <div class="space-y-2">
                            <Label
                                for="leverage"
                                :value="$t('public.leverage')"
                            />
                            <BaseListbox
                                :options="leverageSel"
                                v-model="form.leverage"
                            />
                            <InputError :message="form.errors.leverage"/>
                        </div>
                        <div class="mt-6 space-y-4">
                            <h3 class="text-gray-400 dark:text-gray-300 font-bold text-sm">{{
                                    $t('public.terms_and_conditions')
                                }}</h3>
                            <ol class="text-gray-500 dark:text-gray-400 text-xs list-decimal text-justify pl-6 mt-2">
                                <li>{{ $t('public.terms_1') }}</li>
                                <li>{{ $t('public.terms_2') }}</li>
                                <li>{{ $t('public.terms_3') }}</li>
                                <li>{{ $t('public.terms_4') }}</li>
                                <li>{{ $t('public.terms_5') }}</li>
                                <li>{{ $t('public.terms_6') }}</li>
                                <li>{{ $t('public.terms_7') }}</li>
                                <li>{{ $t('public.terms_9') }}</li>
                            </ol>

                            <div class="flex items-center">
                                <div class="flex items-center h-5">
                                    <Checkbox id="terms" v-model="form.terms"/>
                                </div>
                                <div class="ml-3">
                                    <label for="terms" class="text-gray-500 dark:text-gray-400 text-xs">{{
                                            $t('public.accept_terms')
                                        }}</label>
                                </div>
                            </div>
                            <InputError :message="form.errors.terms"/>

                        </div>
                    </TabPanel>
                </TabPanels>
            </TabGroup>
            <div class="mt-6 flex justify-end">
                <Button
                    type="button"
                    variant="primary-transparent"
                    @click="closeModal">
                    {{ $t('public.cancel') }}
                </Button>

                <Button
                    variant="primary"
                    class="ml-3"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    @click="submit"
                >
                    {{ $t('public.process') }}
                </Button>
            </div>
        </form>
    </Modal>
</template>
