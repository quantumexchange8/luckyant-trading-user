<script setup>
import Button from "@/Components/Button.vue";
</script>

<template>
    <div
        class="flex flex-col items-start gap-3 border border-gray-400 dark:border-gray-600 rounded-lg p-5 max-w-md"
    >
        <div class="flex justify-between items-center self-stretch">
            <div class="flex items-center gap-3">
                <div>
                    Logo
                </div>
                <div class="flex flex-col items-start">
                    <div>
                        Standard Account
                    </div>
                    <div>
                        1728612
                    </div>
                </div>
            </div>
            <div>
                Active
            </div>
        </div>
        <div class="flex justify-between items-center self-stretch">
            <div class="flex items-center gap-3">
                <div>
                    Leverage
                </div>
                <div>
                    Credit
                </div>
            </div>
            <div>
                Account Balance
            </div>
        </div>
        <div class="flex items-center gap-3">
            <Button
                type="button"
                class="flex justify-center"
            >
                Deposit
            </Button>
            <Button
                type="button"
                variant="black"
                class="flex justify-center"
            >
                More
            </Button>
        </div>
    </div>
</template>
