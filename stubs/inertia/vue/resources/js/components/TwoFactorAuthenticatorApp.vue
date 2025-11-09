<script setup lang="ts">
import { authenticatorApp } from '@/pages/settings/TwoFactor.vue';
import authenticatorAppTwoFactor from '@/routes/authenticator-app-two-factor';
import passwordConfirmation from '@/routes/password-confirmation';
import { router, useForm } from '@inertiajs/vue3';
import { useClipboard } from '@vueuse/core';
import { ComputedRef, inject, nextTick, ref, Ref, watch } from 'vue';
import axios from 'axios';
import { Switch } from '@headlessui/vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    PinInput,
    PinInputGroup,
    PinInputSlot,
} from '@/components/ui/pin-input';
import { Check, Copy, Loader2, ScanLine } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import ConfirmPasswordDialog from '@/components/ConfirmPasswordDialog.vue'; 

const context = inject<{
    recoveryCodesDialog: Ref<boolean, boolean>;
    authenticatorAppFeature: ComputedRef<authenticatorApp>;
    recoveryCodesRequireTwoFactorEnabled: boolean;
}>("context");


const status = ref<"enabling" | "confirming" | "disabling" | null>(null);

const qrCode = ref(null);
const setupKey = ref<string|null>(null);

const confirmingPasswordDialog = ref<boolean>(false);

let onPasswordConfimedMethod: "enableTwoFactorAuthentication" | "disableTwoFactorAuthentication" | "confirmTwoFactorAuthentication" | any = null;

const pinInputContainerRef = ref<HTMLElement | null>(null);

const codeArray = ref<number[]>([]);
const confirmationForm = useForm({
    code: "",
});

watch(codeArray, (value)=> {
    confirmationForm.code = value.join('');
})

const twoFactorAuthenticationEnabled = ref(context?.authenticatorAppFeature.value.userEnabled);

const showVerificationStep = ref<boolean>(false);

watch(showVerificationStep, (value) => {
    if(value)
        nextTick(() => {
            pinInputContainerRef.value?.querySelector('input')?.focus();
        });
})

const { copy, copied } = useClipboard();

const OTP_MAX_LENGTH = 6;

const closeDialog = () =>{
    if (context?.authenticatorAppFeature.value.requiresConfirmation) {
        disableTwoFactorAuthentication();
    } else {
        qrCode.value = null;
        setupKey.value = null;
    }

    if (showVerificationStep) {
        showVerificationStep.value = false;
    }
}

watch(twoFactorAuthenticationEnabled, (value)=>{
    if (value === false)
        confirmationForm.resetAndClearErrors();
});

const enableTwoFactorAuthentication = () => {
    status.value = "enabling";
    setTimeout(() => {
        router.post(authenticatorAppTwoFactor.enable().url, {}, {
            preserveScroll: true,
            onSuccess: () => Promise.all([
                showQrCode(),
                showSetupKey(),
                twoFactorAuthenticationEnabled.value = (true),
                status.value = (context?.authenticatorAppFeature.value.requiresConfirmation) ? "confirming" : null
            ]),
            onError: () => {
                status.value = null;
            },

        });
    }, 0);
};

const confirmTwoFactorAuthentication = () => {
    confirmationForm.post(authenticatorAppTwoFactor.confirm().url, {
        errorBag: "confirmTwoFactorAuthentication",
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            status.value = null;
            qrCode.value = null;
            setupKey.value = null;
            
            if (context && context.recoveryCodesRequireTwoFactorEnabled) {
                context.recoveryCodesDialog.value = true;
            }
        },
    });
};

const disableTwoFactorAuthentication = () => {
    let previousStatus = status.value;
    
    status.value = "disabling";

    setTimeout(() => {
        router.delete(authenticatorAppTwoFactor.disable().url, {
            preserveScroll: true,
            onSuccess: () => {
                twoFactorAuthenticationEnabled.value = false;
                status.value = null;
            },
            onError: () => {
                if (previousStatus === "confirming") status.value = previousStatus;
            }
        });
    }, 0);
};

const openPasswordDialog = () => {

    if (context?.authenticatorAppFeature.value.requirePasswordConfirmation) {
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
    if (onPasswordConfimedMethod === 'enableTwoFactorAuthentication') {
        enableTwoFactorAuthentication();
    } else if (onPasswordConfimedMethod === 'disableTwoFactorAuthentication') {
        disableTwoFactorAuthentication();
    } else if (onPasswordConfimedMethod === 'confirmTwoFactorAuthentication') {
        confirmTwoFactorAuthentication();
    }
}

const showQrCode = async () => {
    const response = await axios.get(authenticatorAppTwoFactor.qrCode().url);
    qrCode.value = response.data.svg;
};

const showSetupKey = async () => {
    const response = await axios.get(authenticatorAppTwoFactor.secretKey().url);
    setupKey.value = response.data.secretKey;
}

const checkboxChanged = () => {
    
    if (! twoFactorAuthenticationEnabled.value) {
        onPasswordConfimedMethod = "enableTwoFactorAuthentication"
        openPasswordDialog();
    } else {
        onPasswordConfimedMethod = "disableTwoFactorAuthentication"
        openPasswordDialog();
    }
}
</script>

<template>
    <div>
        <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 flex items-center justify-between">
            <span>Authenticator app</span>

            <Switch
                v-on:click="(e: PointerEvent) => {
                    if ((status === 'enabling') || (status === 'disabling')) {
                        e.preventDefault();
                        return;
                    } 
                    checkboxChanged();
                }"
                :defaultChecked="twoFactorAuthenticationEnabled"
                :class="(twoFactorAuthenticationEnabled ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700') + 
                    (((status === 'enabling') || (status === 'disabling')) ? ' opacity-50' : '') "
                class="inline-flex h-6 w-11 items-center rounded-full transition cursor-pointer"
                >
                <span
                    aria-hidden="true"
                    :class="twoFactorAuthenticationEnabled ? 'translate-x-6' : 'translate-x-0'"
                    class="size-4 translate-x-1 rounded-full bg-white transition"
                />
            </Switch>
        </h3>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Use a mobile authenticator app to get a verification code to enter every time you log in</p>

        <Dialog :open="twoFactorAuthenticationEnabled && (setupKey!=null) && (qrCode!=null)" @update:open="!$event && closeDialog()">
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
                            <ScanLine
                                class="relative z-20 size-6 text-foreground"
                            />
                        </div>
                    </div>

                    <DialogTitle v-if="showVerificationStep">
                        Verify Authentication Code
                    </DialogTitle>
                    <DialogTitle v-else-if="showVerificationStep">
                        Enable Two-Factor Authentication
                    </DialogTitle>
                    <DialogTitle v-else>
                        Two-Factor Authentication Enabled
                    </DialogTitle>

                    <DialogDescription class="text-center" v-if="showVerificationStep">
                        Enter the 6-digit code from your authenticator app
                    </DialogDescription>
                    <DialogDescription class="text-center" v-else-if="context?.authenticatorAppFeature.value.requiresConfirmation">
                        To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app
                    </DialogDescription>
                    <DialogDescription class="text-center" v-else>
                        Two-factor authentication is now enabled. Scan the following QR code using your phone's authenticator application or enter the setup key
                    </DialogDescription>
                </DialogHeader>

                <div v-if="showVerificationStep" class="relative w-full space-y-3">
                    <div class="flex w-full flex-col items-center space-y-3 py-2" ref="pinInputContainerRef">
                        <PinInput
                            id="otp"
                            v-model="codeArray"
                            type="number"
                            otp
                            @keydown="(e: KeyboardEvent)=>{
                                if (e.key !== 'Enter') return;
                                if (confirmationForm.processing || confirmationForm.code.length < OTP_MAX_LENGTH) return;
                                confirmTwoFactorAuthentication();
                            }"
                        >
                            <PinInputGroup>
                                <PinInputSlot
                                    autofocus
                                    v-for="(id, index) in OTP_MAX_LENGTH"
                                    :key="id"
                                    :index="index"
                                    :disabled="confirmationForm.processing"
                                />
                            </PinInputGroup>
                        </PinInput>
                        <InputError
                            :message="
                                confirmationForm.errors.code
                            "
                        />
                    </div>

                    <div class="flex w-full space-x-5">
                        <Button
                            type="button"
                            variant="outline"
                            class="flex-1"
                            @click="()=>{showVerificationStep = false}"
                        >
                            Back
                        </Button>
                        <Button
                            type="button"
                            class="flex-1"
                            :disabled="
                                confirmationForm.processing || confirmationForm.code.length < OTP_MAX_LENGTH
                            "
                            @click="() =>{
                                onPasswordConfimedMethod = 'confirmTwoFactorAuthentication';
                                openPasswordDialog();
                            }"
                        >
                            Confirm
                        </Button>
                    </div>
                </div>

                <div v-else class="flex flex-col items-center space-y-5">
                    <div class="mx-auto flex max-w-md overflow-hidden">
                        <div class="mx-auto aspect-square w-64 rounded-lg border border-border dark:bg-white">
                            <div class="z-10 flex h-full w-full items-center justify-center p-5">
                                <div
                                    v-if="!qrCode"
                                    class="absolute inset-0 z-10 flex aspect-square h-auto w-full animate-pulse items-center justify-center bg-background"
                                >
                                    <Loader2 class="size-6 animate-spin" />
                                </div>
                                <div
                                    v-else
                                >
                                    <div
                                        v-html="qrCode"
                                        class="flex aspect-square size-full items-center justify-center"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="context?.authenticatorAppFeature.value.requiresConfirmation" class="flex w-full space-x-5">
                        <Button class="w-full" @click="()=> showVerificationStep = true">
                            Continue
                        </Button>
                    </div>
                    <div v-else class="flex w-full space-x-5">
                        <Button class="w-full" @click="() => closeDialog()">
                            Close
                        </Button>
                    </div>

                    <div class="relative flex w-full items-center justify-center">
                        <div class="absolute inset-0 top-1/2 h-px w-full bg-border" />
                        <span class="relative bg-card px-2 py-1">
                            or, enter the code manually
                        </span>
                    </div>

                    <div class="flex w-full space-x-2">
                        <div v-if="!setupKey" class="flex h-full w-full items-center justify-center bg-muted p-3">
                            <Loader2 class="size-4 animate-spin" />
                        </div>
                        <div v-else class="flex w-full items-stretch overflow-hidden rounded-xl border border-border">
                            <input
                                type="text"
                                readOnly
                                :value="setupKey"
                                class="h-full w-full bg-background p-3 text-foreground outline-none"
                            />
                            <button
                                @click="() => { 
                                    if(setupKey != null) { 
                                        copy(setupKey)
                                    }
                                }"
                                class="border-l border-border px-3 hover:bg-muted"
                            >
                                <Check
                                    v-if="copied"
                                    class="w-4 text-green-500"
                                />
                                <Copy v-else class="w-4" />
                            </button>
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