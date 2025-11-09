<script setup lang="ts">
import { email } from '@/pages/settings/TwoFactor.vue';
import emailTwoFactor from '@/routes/email-two-factor';
import { router, useForm } from '@inertiajs/vue3';
import { inject, ref, Ref, watch, nextTick, ComputedRef } from 'vue';
import axios from 'axios';
import passwordConfirmation from '@/routes/password-confirmation';
import ConfirmPasswordDialog from './ConfirmPasswordDialog.vue';
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
import { Mail } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';

const context = inject<{
    recoveryCodesDialog: Ref<boolean, boolean>;
    emailFeature: ComputedRef<email>;
    recoveryCodesRequireTwoFactorEnabled: boolean;
}>("context");

const status = ref<"enabling" | "confirming" | "disabling" | null>(null);

const confirmingPasswordDialog = ref<boolean>(false);

const twoFactorDialog = ref<boolean>(false);

let onPasswordConfimedMethod: "enableTwoFactorAuthentication" | "disableTwoFactorAuthentication" | "confirmTwoFactorAuthentication" | any = null;

const pinInputContainerRef = ref<HTMLElement | null>(null);

watch(twoFactorDialog, (value) => {
    if(context?.emailFeature.value.requiresConfirmation && value)
        nextTick(() => {
            pinInputContainerRef.value?.querySelector('input')?.focus();
        });
})

const codeArray = ref<number[]>([]);
const confirmationForm = useForm({
    code: '',
});

watch(codeArray, (value)=> {
    confirmationForm.code = value.join('');
});

const sendCodeForm = useForm<{
    [key: string]: string | undefined
}>({});

const OTP_MAX_LENGTH = 6;

const twoFactorAuthenticationEnabled = ref(context?.emailFeature.value.userEnabled);

watch(twoFactorAuthenticationEnabled, (value)=>{
    if (value === false)
        confirmationForm.resetAndClearErrors();
});

const closeDialog = () =>{
    disableTwoFactorAuthentication();
}

const enableTwoFactorAuthentication = () => {
    status.value = "enabling";
    setTimeout(() => {
        router.post(emailTwoFactor.enable().url, {}, {
            preserveScroll: true,
            onSuccess: () => Promise.all([
                twoFactorAuthenticationEnabled.value = true,
                twoFactorDialog.value = true,
                status.value = ((context?.emailFeature.value.requiresConfirmation) ? "confirming" : null)
            ]),
            onError: () => {    
                status.value = null;
            },
        });
    }, 0);
};

const sendCode = () =>{

    sendCodeForm.post(emailTwoFactor.sendCode().url, {
        errorBag: "EmailTwoFactorAuthenticationNotification",
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {

        },
    })
}

const confirmTwoFactorAuthentication = () => {
    confirmationForm.post(emailTwoFactor.confirm().url, {
        errorBag: "confirmEmailTwoFactorAuthentication",
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            status.value = null;
            twoFactorDialog.value = false;

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
        router.delete(emailTwoFactor.disable().url, {
            preserveScroll: true,
            onSuccess: () => {
                status.value = null;
                twoFactorAuthenticationEnabled.value = false;
                twoFactorDialog.value = false;
            },
            onError: () => {
                if (previousStatus === "confirming") status.value = previousStatus;
            }
        });
    }, 0);
};

const openPasswordDialog = () => {
    if (context?.emailFeature.value.requirePasswordConfirmation) {
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
            <span>Email</span>

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
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Use your email to receive an authentication code to enter when you log in.</p>

        <Dialog :open="context?.emailFeature.value.requiresConfirmation && twoFactorDialog" @update:open="!$event && closeDialog()">
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
                            <Mail
                                class="relative z-20 size-6 text-foreground"
                            />
                        </div>
                    </div>

                    <DialogTitle>
                        Entar OTP Code
                    </DialogTitle>

                    <DialogDescription class="text-center">
                        Please confirm access to your account by entering the OTP that was sent to your email
                    </DialogDescription>

                    <DialogDescription class="text-center">
                        Didn't receive code? {{'   '}}
                        <button @click="sendCode" class="cursor-pointer text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500" :class="sendCodeForm.processing ? 'opacity-25' : ''">Resend</button>
                        <InputError :message="sendCodeForm.errors.attempts" class="mt-2" />
                    </DialogDescription>

                </DialogHeader>

                <div class="relative w-full space-y-3">
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
                            @click="()=>{closeDialog()}"
                        >
                            Cancel
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
                
            </DialogContent>
        </Dialog>

        <ConfirmPasswordDialog
            :is-open="confirmingPasswordDialog"
            @onPasswordConfimed="onPasswordConfimed"
            @toggle="confirmingPasswordDialog = !confirmingPasswordDialog"
        />
    </div>
</template>