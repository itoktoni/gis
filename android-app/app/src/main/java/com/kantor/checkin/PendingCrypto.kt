package com.kantor.checkin

import android.security.keystore.KeyGenParameterSpec
import android.security.keystore.KeyProperties
import java.security.KeyStore
import javax.crypto.Cipher
import javax.crypto.KeyGenerator
import javax.crypto.SecretKey
import javax.crypto.spec.GCMParameterSpec

/**
 * Enkripsi antrean offline (AES-256-GCM, kunci di Android Keystore).
 * File pending (.jpg.bin + .meta.bin) tidak bisa dibaca tanpa kunci perangkat.
 */
object PendingCrypto {
    private const val ALIAS = "pending_key"

    private fun getKey(): SecretKey {
        val ks = KeyStore.getInstance("AndroidKeyStore").apply { load(null) }
        (ks.getEntry(ALIAS, null) as? KeyStore.SecretKeyEntry)?.let { return it.secretKey }
        val kg = KeyGenerator.getInstance(KeyProperties.KEY_ALGORITHM_AES, "AndroidKeyStore")
        kg.init(KeyGenParameterSpec.Builder(ALIAS,
            KeyProperties.PURPOSE_ENCRYPT or KeyProperties.PURPOSE_DECRYPT)
            .setBlockModes(KeyProperties.BLOCK_MODE_GCM)
            .setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE)
            .setKeySize(256)
            .build())
        return kg.generateKey()
    }

    /** IV 12 byte + ciphertext. */
    fun encryptBytes(plain: ByteArray): ByteArray {
        val c = Cipher.getInstance("AES/GCM/NoPadding")
        c.init(Cipher.ENCRYPT_MODE, getKey())
        val ct = c.doFinal(plain)
        return c.iv + ct
    }

    fun decryptBytes(blob: ByteArray): ByteArray {
        val iv = blob.copyOfRange(0, 12)
        val ct = blob.copyOfRange(12, blob.size)
        val c = Cipher.getInstance("AES/GCM/NoPadding")
        c.init(Cipher.DECRYPT_MODE, getKey(), GCMParameterSpec(128, iv))
        return c.doFinal(ct)
    }

    fun encryptText(s: String): ByteArray = encryptBytes(s.toByteArray(Charsets.UTF_8))
    fun decryptText(blob: ByteArray): String = decryptBytes(blob).toString(Charsets.UTF_8)
}
