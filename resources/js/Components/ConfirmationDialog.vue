<script setup>
import ConfirmDialog from 'primevue/confirmdialog';
import Button from "@/Components/Button.vue";
import {
    CreditCardAddIcon
} from "@/Components/Icons/outline.jsx";
import {
    IconPasswordMobilePhone,
    IconUserCancel,
    IconPassword,
} from "@tabler/icons-vue";
</script>

<template>
    <ConfirmDialog group="headless-primary">
        <template #container="{ message, acceptCallback, rejectCallback }">
            <div class="flex flex-col items-center bg-white dark:bg-gray-900 rounded-lg w-[320px] h-[250px] md:w-[500px] md:h-[300px]">
                <div class="relative w-full h-[93px] md:h-[132px] flex">
                    <div
                        class="rounded-tl-lg rounded-tr-lg flex w-full p-1 justify-center items-center bg-primary-600 dark:bg-primary-800"
                    >
                        <div class="p-3 md:p-5 flex items-center justify-center rounded-full grow-0 shrink-0 bg-primary-100 w-14 md:w-24 h-14 md:h-24">
                            <!-- Conditionally render the icon based on actionType -->
                            <CreditCardAddIcon
                                v-if="message.actionType === 'add_payment_account'"
                                class="w-16 h-16 text-primary-700"
                            />
                            <div
                                v-if="message.actionType === 'add_security_pin'"
                                class="text-primary-700"
                            >
                                <IconPasswordMobilePhone
                                    stroke-width="1.5"
                                    size="64"
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pt-2 md:pt-4 pb-6 px-4 md:px-6 w-full flex flex-col items-center gap-5 self-stretch">
                    <div class="flex flex-col gap-1 items-center self-stretch text-center">
                        <span class="text-gray-950 dark:text-white text-sm md:text-base font-semibold">{{ message.header }}</span>
                        <div class="text-gray-700 dark:text-gray-400 text-xs md:text-sm">
                            {{ message.message.text }}
                            <div class="flex flex-col justify-center">
                                <span class="text-gray-950 font-semibold">{{ message.message.dynamicText }}</span>
                                <span>{{ message.message.suffix }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 md:gap-5 self-stretch">
                        <Button
                            type="button"
                            variant="transparent"
                            @click="rejectCallback"
                            class="w-full justify-center"
                            size="base"
                        >
                            {{ message.cancelButton }}
                        </Button>
                        <Button
                            type="button"
                            variant="primary"
                            @click="acceptCallback"
                            class="w-full justify-center text-nowrap"
                            size="base"
                        >
                            {{ message.acceptButton }}
                        </Button>
                    </div>
                </div>
            </div>
        </template>
    </ConfirmDialog>

    <ConfirmDialog group="headless-gray">
        <template #container="{ message, acceptCallback, rejectCallback }">
            <div class="flex flex-col items-center bg-white rounded-3xl w-[320px] h-[250px] md:w-[500px] md:h-[300px]">
                <div class="relative w-full h-[93px] md:h-[132px] flex">
                    <div
                        class="rounded-tl-3xl rounded-tr-3xl flex w-full p-1 justify-center [clip-path:polygon(0%_0%,_100%_0%,_100%_78%,_50%_100%,_0_78%)] bg-gray-900"
                    >
                        <div class="p-5 flex items-center justify-center">
                        </div>
                    </div>
                </div>
                <div class="pt-2 md:pt-3 pb-6 px-4 md:px-6 w-full flex flex-col items-center gap-5 self-stretch">
                    <div class="flex flex-col gap-1 items-center self-stretch text-center">
                        <span class="text-gray-950 text-sm md:text-base font-semibold">{{ message.header }}</span>
                        <span class="text-gray-700 text-xs md:text-sm">
                            {{ message.message.text }}
                            <span class="text-gray-950 font-semibold">{{ message.message.dynamicText }}</span>
                            {{ message.message.suffix }}
                        </span>
                    </div>
                    <div class="flex items-center gap-4 md:gap-5 self-stretch">
                        <Button
                            v-if="message.cancelButton"
                            type="button"
                            variant="gray-tonal"
                            @click="rejectCallback"
                            class="w-full"
                            size="base"
                        >
                            {{ message.cancelButton }}
                        </Button>
                        <Button
                            type="button"
                            variant="gray-flat"
                            @click="acceptCallback"
                            class="w-full text-nowrap"
                            size="base"
                        >
                            {{ message.acceptButton }}
                        </Button>
                    </div>
                </div>
            </div>
        </template>
    </ConfirmDialog>

    <ConfirmDialog group="headless-error">
        <template #container="{ message, acceptCallback, rejectCallback }">
            <div class="flex flex-col items-center bg-white dark:bg-gray-900 rounded-lg w-[320px] h-[250px] md:w-[500px] md:h-[300px]">
                <div class="relative w-full h-[93px] md:h-[132px] flex">
                    <div
                        class="rounded-tl-lg rounded-tr-lg flex w-full p-1 justify-center items-center bg-error-600 dark:bg-error-800"
                    >
                        <div class="p-3 md:p-5 flex items-center justify-center rounded-full grow-0 shrink-0 bg-error-100 w-14 md:w-24 h-14 md:h-24">
                            <!-- Conditionally render the icon based on actionType -->
                            <div
                                v-if="message.actionType === 'verify_kyc'"
                                class="text-error-700"
                            >
                                <IconUserCancel
                                    stroke-width="1.5"
                                    size="64"
                                />
                            </div>
                            <div
                                v-if="message.actionType === 'password_changed'"
                                class="text-error-700"
                            >
                                <IconPassword
                                    stroke-width="1.5"
                                    size="64"
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pt-2 md:pt-4 pb-6 px-4 md:px-6 w-full flex flex-col items-center gap-5 self-stretch">
                    <div class="flex flex-col gap-1 items-center self-stretch text-center">
                        <span class="text-gray-950 dark:text-white text-sm md:text-base font-semibold">{{ message.header }}</span>
                        <div class="text-gray-700 dark:text-gray-400 text-xs md:text-sm">
                            {{ message.message.text }}
                            <div class="flex flex-col justify-center">
                                <span class="text-gray-950 font-semibold">{{ message.message.dynamicText }}</span>
                                <span>{{ message.message.suffix }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 md:gap-5 self-stretch">
                        <Button
                            type="button"
                            variant="transparent"
                            @click="rejectCallback"
                            class="w-full justify-center"
                            size="base"
                        >
                            {{ message.cancelButton }}
                        </Button>
                        <Button
                            v-if="message.acceptButton"
                            type="button"
                            variant="danger"
                            @click="acceptCallback"
                            class="w-full justify-center text-nowrap"
                            size="base"
                        >
                            {{ message.acceptButton }}
                        </Button>
                    </div>
                </div>
            </div>
        </template>
    </ConfirmDialog>
</template>
