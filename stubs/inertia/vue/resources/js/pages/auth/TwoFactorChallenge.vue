<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    PinInput,
    PinInputGroup,
    PinInputSlot,
} from '@/components/ui/pin-input';
import AuthLayout from '@/layouts/AuthLayout.vue';
import emailTwoFactor from '@/routes/email-two-factor';
import passkeysTwoFactor from '@/routes/passkeys-two-factor';
import twoFactor from '@/routes/two-factor';
import { Form, Head, useForm } from '@inertiajs/vue3';
import { startAuthentication } from '@simplewebauthn/browser';
import {  nextTick, ref, watch } from 'vue';
import axios from 'axios';
import { Card } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { LoaderCircle } from 'lucide-vue-next';

interface form {
    code: string;
    recovery_code: string;
    email_code: string;
    [key: string]: string | undefined; //allow for extra keys
}

interface sendCodeForm {
    [key: string]: string | undefined; //allow for extra keys
}

const props = defineProps<{
    twoFactorMethod: "code" | "email_code" | "recovery_code" | "passkeys" | null;
    twoFactorEnabled: {
        authenticatorApp: boolean;
        email: boolean;
        recoveryCodes: boolean;
        passkeys: boolean;
    };
}>();

const twoFactorAuthenticationMethod = ref(props.twoFactorMethod);

const codeInputContainer = ref<HTMLDivElement | null>(null);
const recoveryCodeInput = ref<HTMLInputElement | null>(null);
const emailInputContainer = ref<HTMLDivElement | null>(null);

const OTP_MAX_LENGTH = 6;

const form = useForm<form>({
    code: '',
    recovery_code: '',
    email_code: '',
    passkey: ""
});

const sendCodeForm = useForm<sendCodeForm>({});

const codeArray = ref<number[]>([]);

watch(codeArray, (value) => {
    if (twoFactorAuthenticationMethod.value == 'code') {
        form.code = value.join('');
    } else if (twoFactorAuthenticationMethod.value == 'email_code') {
        form.email_code = value.join('');
    }
})

watch(twoFactorAuthenticationMethod, (value) => {
    if (props.twoFactorEnabled.recoveryCodes && value == 'recovery_code') {
        recoveryCodeInput.value?.focus();

    } else if (props.twoFactorEnabled.authenticatorApp && value == 'code') {
        codeInputContainer.value?.querySelector('input')?.focus();

    } else if (props.twoFactorEnabled.email && value == 'email_code') {
        emailInputContainer.value?.querySelector('input')?.focus();

        if (!sendCodeForm.wasSuccessful) {
            setTimeout(()=>{
                sendCode();
            }, 1)
        }

    }
    codeArray.value = [];
    form.resetAndClearErrors();
})

const sendCode = () =>{
    sendCodeForm.post(emailTwoFactor.sendCode().url, {
        errorBag: "EmailTwoFactorAuthenticationNotification",
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {

        },
    })
}

const submit = () => {
    if (twoFactorAuthenticationMethod.value !== "passkeys")
        form.post(twoFactor.login().url);
    else 
        passkeyAuthenticate();
};

const passkeyAuthenticate = async () => {
    const options = await axios.get(passkeysTwoFactor.authenticateOptions().url)

    try {
        const passkey = await startAuthentication({
            optionsJSON: options.data
        });
    
        form.passkey = JSON.stringify(passkey);
    
        await nextTick();
    
        form.post(passkeysTwoFactor.authenticate().url)
    } catch (error) {
        form.setError('passkey', "Passkey login failed. please try again.");
        console.error(error);
    }
};

watch(twoFactorAuthenticationMethod, (value) => {
    if (value === "passkeys")
        passkeyAuthenticate();
});

const getContent = (content: "title" | "description") => {
    if (props.twoFactorEnabled.recoveryCodes && twoFactorAuthenticationMethod.value == 'recovery_code') {
        if (content === "title")
            return ('Recovery Code');
        else if (content === "description")
            return ('Please confirm access to your account by entering one of your emergency recovery codes.');

    } else if (props.twoFactorEnabled.authenticatorApp && twoFactorAuthenticationMethod.value == 'code') {
        if (content === "title")
            return ('Authenticator App');
        else if (content === "description")
            return ('Please confirm access to your account by entering the authentication code provided by your authenticator application.');

    } else if (props.twoFactorEnabled.email && twoFactorAuthenticationMethod.value == 'email_code') {
        if (content === "title")
            return ('Email');
        else if (content === "description")
            return ('Please confirm access to your account by entering the OTP that was sent to your email.');

    } else if (props.twoFactorEnabled.passkeys && twoFactorAuthenticationMethod.value == 'passkeys') {
        if (content === "title")
            return ('Passkey');
        else if (content === "description")
            return ('Please confirm access to your account by validating your passkey.');

    } else {
        if (content === "title")
            return ('Two-Factor Authentication');
        else if (content === "description")
            return ('Please choose any of the following two factor method.');
    }
};

</script>

<template>
    <AuthLayout
        :title="getContent('title')"
        :description="getContent('description')"
    >
        <Head title="Two-Factor Authentication" />

        <div v-if="!twoFactorAuthenticationMethod">
            <Card class="mt-4 p-4 gap-3">
                    
                <div v-if="twoFactorEnabled.authenticatorApp" class="group hover:cursor-pointer" @click="()=>{twoFactorAuthenticationMethod = 'code'}">
                    <h3 class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out group-hover:decoration-current! dark:decoration-neutral-500">Authenticator App</h3>
                    <p class="mt-1 text-sm">
                        Use a mobile authenticator app to get a verification code to log in
                    </p>
                </div>

                <Separator v-if="twoFactorEnabled.authenticatorApp" class="last:hidden"/>
                
                <div v-if="twoFactorEnabled.email" class="group hover:cursor-pointer" @click="()=>{twoFactorAuthenticationMethod = 'email_code'}">
                    <h3 class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out group-hover:decoration-current! dark:decoration-neutral-500">Email</h3>
                    <p class="mt-1 text-sm">
                        Use your email to receive an authentication code to log in.
                    </p>
                </div>

                <Separator v-if="twoFactorEnabled.email" class="last:hidden"/>

                    
                <div v-if="twoFactorEnabled.passkeys" class="group hover:cursor-pointer" @click="()=>{twoFactorAuthenticationMethod = 'passkeys'}">
                    <h3 class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out group-hover:decoration-current! dark:decoration-neutral-500">Passkey</h3>
                    <p class="mt-1 text-sm">
                        Use your registerd passkey to login.
                    </p>
                </div>

                <Separator v-if="twoFactorEnabled.passkeys" class="last:hidden"/>
                
                <div v-if="twoFactorEnabled.recoveryCodes" class="group hover:cursor-pointer" @click="()=>{twoFactorAuthenticationMethod = 'recovery_code'}">
                    <h3 class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out group-hover:decoration-current! dark:decoration-neutral-500">Recovery Code</h3>
                    <p class="mt-1 text-sm">
                        Single-use recovery codes that lets you log in if you don’t have access to your two-factor authentication options.
                    </p>
                </div>
                    
            </Card>
        </div>

        <div v-else>
            <form @submit.prevent="submit" class="flex flex-col gap-4">
                <div class="grid gap-4">

                    <div v-if="twoFactorAuthenticationMethod === 'code'" class="flex flex-col items-center justify-center space-y-3 text-center">
                        <div class="flex w-full items-center justify-center" ref="codeInputContainer">
                            <PinInput
                                id="code"
                                name="code"
                                v-model="codeArray"
                                type="number"
                                otp
                            >
                                <PinInputGroup>
                                    <PinInputSlot
                                        autofocus
                                        v-for="(id, index) in OTP_MAX_LENGTH"
                                        :key="id"
                                        :index="index"
                                        :disabled="form.processing"
                                    />
                                </PinInputGroup>
                            </PinInput>
                        </div>
                        <InputError :message="form.errors.code" />
                        <InputError :message="form.errors.empty" />
                        <InputError :message="form.errors.attempts" />
                    </div>

                    <div v-if="twoFactorAuthenticationMethod === 'recovery_code'" className="grid gap-2">
                        <Input
                            id="recovery_code"
                            type="text"
                            name="recovery_code"
                            ref={recoveryCodeInput}
                            required
                            :tabindex="1"
                            placeholder="Enter recovery code"
                            v-model="form.recovery_code"
                        />
                        <InputError :message="form.errors.recovery_code" />
                        <InputError :message="form.errors.empty" />
                        <InputError :message="form.errors.attempts" />
                    </div>

                    <div v-if="twoFactorAuthenticationMethod === 'email_code'" class="flex flex-col items-center justify-center space-y-3 text-center">

                        <span class="text-center text-sm text-muted-foreground">
                            Didn't receive code? {{'   '}}
                            <button type="button" @click="sendCode" class="cursor-pointer text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500" :class="sendCodeForm.processing ? 'opacity-25' : ''">Resend</button>
                        </span>

                        <div class="flex w-full items-center justify-center" ref="codeInputContainer">
                            <PinInput
                                id="code"
                                name="code"
                                v-model="codeArray"
                                type="number"
                                otp
                            >
                                <PinInputGroup>
                                    <PinInputSlot
                                        autofocus
                                        v-for="(id, index) in OTP_MAX_LENGTH"
                                        :key="id"
                                        :index="index"
                                        :disabled="form.processing"
                                    />
                                </PinInputGroup>
                            </PinInput>
                        </div>
                        <InputError :message="form.errors.email_code" />
                        <InputError :message="form.errors.empty" />
                        <InputError :message="form.errors.attempts" />
                        <InputError :message="sendCodeForm.errors.attempts" />

                    </div>

                    <div v-if="twoFactorAuthenticationMethod === 'passkeys'">
                        <InputError :message="form.errors.passkey" />
                        <InputError :message="form.errors.attempts" />
                    </div>

                    <Button type="submit" class="w-full" :tabindex="4" :disabled="form.processing">
                        <LoaderCircle
                            v-if="form.processing"
                            class="h-4 w-4 animate-spin"
                        />
                        Log in
                    </Button>

                </div>

                <div class="text-center text-sm text-muted-foreground">
                    <button
                        type="button"
                        class="cursor-pointer text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                        @click="() => twoFactorAuthenticationMethod = null"
                    >
                        choose a different method
                    </button>
                </div>
            </form>
        </div>

    </AuthLayout>
</template>
