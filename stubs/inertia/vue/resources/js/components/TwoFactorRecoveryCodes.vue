<script setup lang="ts">
import twoFactorRecoveryCodes from '@/routes/two-factor-recovery-codes';
import { ComputedRef, inject, ref, Ref, watch } from 'vue';
import axios from 'axios';
import { recoveryCodes } from '@/pages/settings/TwoFactor.vue';
import passwordConfirmation from '@/routes/password-confirmation';
import { router } from '@inertiajs/vue3';
import { Switch } from '@headlessui/vue';
import { Button } from '@/components/ui/button';
import ConfirmPasswordDialog from './ConfirmPasswordDialog.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

import { Clipboard, ClipboardCheck, SquareDashedBottomCode } from 'lucide-vue-next';

const context = inject<{
    recoveryCodesDialog: Ref<boolean, boolean>;
    recoveryCodesFeature: ComputedRef<recoveryCodes>;
    recoveryCodesRequireTwoFactorEnabled: boolean;
}>("context");

const status = ref<"enabling" | "disabling" | null>(null);

const confirmingPasswordDialog = ref<boolean>(false);

const recoveryCodesArray = ref<string[]>([]);

if (context)
watch(context.recoveryCodesDialog, (value) => {
    if (value)
        showRecoveryCodesArray();
});

let onPasswordConfimedMethod: "showRecoveryCodes" | "disableRecoveryCods" | "regenerateRecoveryCodes" | any = null;

const twoFactorRecoveryCodesEnabled = ref(context?.recoveryCodesFeature.value.userEnabled);

const recoveryCodesDiv = ref<HTMLDivElement | null>(null);

const openPasswordDialog = () => {

    if (context?.recoveryCodesFeature.value.confirmsPasswordRecoveryCode) {
        axios.get(passwordConfirmation.show().url).then(response => {
            if (response.data.confirmed) {
                onPasswordConfimed();
            } else {
                confirmingPasswordDialog.value = true;
            }
        });
    } else {
        onPasswordConfimed();
    }
    
};

const onPasswordConfimed = () => {
    if (onPasswordConfimedMethod === 'regenerateRecoveryCodes') {
        regenerateRecoveryCodes();
    } else if (onPasswordConfimedMethod === 'showRecoveryCodes') {
        if(context) context.recoveryCodesDialog.value = true;
    } else if (onPasswordConfimedMethod === 'disableRecoveryCods') {
        disableRecoveryCods();
    }
}

const showRecoveryCodesArray = async () => {
    const response = await axios.get(twoFactorRecoveryCodes.get().url);
    if (response.data.length > 0) {
        recoveryCodesArray.value = response.data;
        twoFactorRecoveryCodesEnabled.value = true;
    }
};

const regenerateRecoveryCodes = () => {
    status.value = "enabling";
    router.post(twoFactorRecoveryCodes.generate().url, {},{
        preserveScroll: true,
        onSuccess: () => {
            if (context && !context.recoveryCodesDialog.value) {
                context.recoveryCodesDialog.value = true;
            } else {
                showRecoveryCodesArray();
            }
        },
        onFinish: () => {
            status.value = null;
        }
    })
};

const disableRecoveryCods = () => {
    status.value = "disabling";

    router.delete(twoFactorRecoveryCodes.disable().url, {
        preserveScroll: true,
        onSuccess: () => {
            twoFactorRecoveryCodesEnabled.value = false;
            recoveryCodesArray.value = [];
        },
        onFinish: () => {
            status.value = null;
        }
    });
};

const copyRecoveryCods = (e: PointerEvent) => {
    const copyButton = e.currentTarget;

    if (copyButton instanceof HTMLButtonElement) {

        const clipboardSvg = copyButton.querySelector('svg');
        const clipboardCopiedSvg = copyButton.querySelector('svg:nth-child(2)');

        copyButton.classList.add('!opacity-100')
        clipboardSvg?.classList.add('hidden');
        clipboardCopiedSvg?.classList.remove('hidden');

        setTimeout(()=>{
            copyButton.classList.remove('!opacity-100')
            clipboardSvg?.classList.remove('hidden');
            clipboardCopiedSvg?.classList.add('hidden');
        }, 1000);

        navigator.clipboard.writeText(recoveryCodesDiv.value ? recoveryCodesDiv.value.innerText : "");
    } else {
        console.log(typeof copyButton);
        
    }
};

const checkboxChanged = () => {
    
    if (! twoFactorRecoveryCodesEnabled.value) {
        onPasswordConfimedMethod = "enableTwoFactorAuthentication"
        openPasswordDialog();
    } else {
        onPasswordConfimedMethod = "disableTwoFactorAuthentication"
        openPasswordDialog();
    }
}

const closeDialog = () => {
    if (context) context.recoveryCodesDialog.value = false;
}

</script>

<template>
    <div>
        <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 flex items-center justify-between">
            <span>Recovery codes</span>

            <Switch
                v-if="context && !context.recoveryCodesRequireTwoFactorEnabled"
                v-on:click="(e: PointerEvent) => {
                    if ((status === 'enabling') || (status === 'disabling')) {
                        e.preventDefault();
                        return;
                    } 
                    checkboxChanged();
                }"
                :defaultChecked="twoFactorRecoveryCodesEnabled"
                :class="(twoFactorRecoveryCodesEnabled ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700') + 
                    (((status === 'enabling') || (status === 'disabling')) ? ' opacity-50' : '') "
                class="inline-flex h-6 w-11 items-center rounded-full transition cursor-pointer"
                >
                <span
                    aria-hidden="true"
                    :class="twoFactorRecoveryCodesEnabled ? 'translate-x-6' : 'translate-x-0'"
                    class="size-4 translate-x-1 rounded-full bg-white transition"
                />
            </Switch>
        </h3>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Single-use recovery codes that lets you log in if you don’t have access to your two-factor authentication options.</p>

        <div class="mt-5.5 flex items-center gap-4.5">
            <Button variant="default" @click="() => {
                onPasswordConfimedMethod = 'showRecoveryCodes';
                openPasswordDialog();
            }">
                Show Recovery Codes
            </Button>
        </div>

        <Dialog :open="context?.recoveryCodesDialog.value && twoFactorRecoveryCodesEnabled" @update:open="!$event && closeDialog()">
            <DialogContent class="sm:max-w-md">
                <DialogHeader class="flex items-center justify-center">
                    <div
                        class="mb-3 w-auto rounded-full border border-border bg-card p-0.5 shadow-sm"
                    >
                        <div
                            class="relative overflow-hidden rounded-full border border-border bg-muted p-2.5"
                        >
                            <div
                                class="absolute inset-0 grid grid-cols-5 opacity-50"
                            >
                                <div
                                    v-for="i in 5"
                                    :key="`col-${i}`"
                                    class="border-r border-border last:border-r-0"
                                />
                            </div>
                            <div
                                class="absolute inset-0 grid grid-rows-5 opacity-50"
                            >
                                <div
                                    v-for="i in 5"
                                    :key="`row-${i}`"
                                    class="border-b border-border last:border-b-0"
                                />
                            </div>
                            <SquareDashedBottomCode
                                class="relative z-20 size-6 text-foreground"
                            />
                        </div>
                    </div>

                    <DialogTitle>
                        Recovery Cods
                    </DialogTitle>

                    <DialogDescription class="text-center">
                        Store these recovery codes in a secure password manager
                    </DialogDescription>
                </DialogHeader>

                <div class="flex flex-col items-center space-y-5">
                    <div class="relative w-full space-y-3">
                        <div class="flex w-full flex-col items-center space-y-3 py-2">
                            <div class="rounded-lg bg-muted p-4 font-mono text-sm flex justify-between w-full">
                                <div ref="recoveryCodesDiv">
                                    <div
                                        v-for="(code, index) in recoveryCodesArray"
                                        :key="index"
                                    >
                                        {{ code }}
                                    </div>
                                </div>
                                <button type="button" class="h-fit opacity-30 hover:opacity-50 focus-visible:outline-none" @click="(e) => copyRecoveryCods(e)">
                                    <Clipboard/>
                                    <ClipboardCheck class="hidden"/>
                                </button>
                            </div>

                            <DialogDescription>
                                If you ever lose access to your two-factor authentication options, you can use these codes to verify your identity.
                            </DialogDescription>
                        </div>

                        <div class="flex w-full space-x-5">
                            <Button
                                type="button"
                                variant="outline"
                                class="flex-1"
                                @click="() => {if(context) context.recoveryCodesDialog.value = false}"
                            >
                                Close
                            </Button>
                            <Button
                                type="button"
                                class="flex-1"
                                @click="() =>{
                                    onPasswordConfimedMethod = 'regenerateRecoveryCodes'
                                    openPasswordDialog();
                                }"
                                :disabled="status === 'enabling'"
                            >
                                Regenerate Codes
                            </Button>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>

        <ConfirmPasswordDialog
            :is-open="confirmingPasswordDialog"
            @onPasswordConfimed="onPasswordConfimed"
            @toggle="confirmingPasswordDialog = !confirmingPasswordDialog"
        />
    </div>
</template>