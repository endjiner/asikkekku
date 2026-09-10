#!/usr/bin/env bash
# Smoke test alur persetujuan end-to-end (PJ -> ... -> PPSPM) via HTTP.
# Butuh: app jalan, MySQL jalan. Password 6 user peran di-set sementara
# lalu dikembalikan. WA otomatis dilewati bila wa_enabled=0.
#
#   BASE=http://localhost/asikkekku_kirim_drawer6 bash tests/smoke_flow.sh
set -u
BASE="${BASE:-http://127.0.0.1:8777}"
MYSQL="${MYSQL:-C:/xampp/mysql/bin/mysql.exe}"
DB="${DB:-asikkekkuv2}"
MY()  { "$MYSQL" -u root "$DB" -N -e "$1"; }
MYT() { "$MYSQL" -u root "$DB" -e "$1"; }
STAMP=$(date +%s); NOSURAT="SMOKE.$STAMP"
FAIL=0

echo "== siapkan 6 user peran (password sementara) =="
ORIG=$(MY "SELECT GROUP_CONCAT(CONCAT(UserID,':',UserPassword)) FROM tb_users WHERE UserID IN (2,3,4,6,7,8)")
MYT "UPDATE tb_users SET UserPassword=MD5('smoke123') WHERE UserID IN (2,3,4,6,7,8)" >/dev/null
restore() { IFS=',' read -ra P <<< "$ORIG"; for kv in "${P[@]}"; do
  uid="${kv%%:*}"; h="${kv#*:}"; MYT "UPDATE tb_users SET UserPassword='$h' WHERE UserID=$uid" >/dev/null; done
  echo "== password 6 user dikembalikan =="; }
trap restore EXIT

jar() { echo "/tmp/smoke_$1.txt"; }
csrf() { grep -i 'csrf_asikkekku' "$(jar "$1")" | awk '{print $NF}' | tail -1; }
login() { local u="$1" j; j=$(jar "$u"); rm -f "$j"
  curl -sS -c "$j" -b "$j" "$BASE/MainPage/Login" -o /dev/null
  curl -sS -c "$j" -b "$j" -o /dev/null -w '%{http_code}' \
    --data-urlencode "Username=$u" --data-urlencode "Password=smoke123" \
    --data-urlencode "csrf_asikkekku=$(csrf "$u")" "$BASE/MainPage/VerifyLogin"; }
post() { local u="$1" path="$2"; shift 2; local j; j=$(jar "$u")
  curl -sS -c "$j" -b "$j" "$BASE/$path" --data-urlencode "csrf_asikkekku=$(csrf "$u")" "$@"; }
check() { if [ "$1" = "$2" ]; then echo "  OK  $3"; else echo "  XX  $3 (dapat '$1', harusnya '$2')"; FAIL=1; fi; }

echo "== 1. login PJ1 =="; check "$(login PJ1)" "303" "login PJ1"
post PJ1 "manajemen_approval/KegiatanModify" \
  --data-urlencode "KegiatanID=" --data-urlencode "KegiatanNoSuratTugas=$NOSURAT" \
  --data-urlencode "KegiatanJudul=SMOKE end-to-end" --data-urlencode "KegiatanTanggal=$(date +%Y-%m-%d)" \
  --data-urlencode "KegiatanNamaPelaksana=Uji Satu, S.T" --data-urlencode "KegiatanPemohonTipe=internal" \
  --data-urlencode "KegiatanPemohonPhone=" --data-urlencode "KegiatanKeterangan=smoke" \
  --data-urlencode "KegiatanJenisID=1" \
  --data-urlencode "KegiatanDestUser=8" --data-urlencode "KegiatanLampiranPrev=" >/dev/null
KID=$(MY "SELECT KegiatanID FROM tb_kegiatan WHERE KegiatanNoSuratTugas='$NOSURAT' LIMIT 1")
check "$([ -n "$KID" ] && echo ada)" "ada" "kegiatan dibuat (ID=$KID)"

post PJ1 "manajemen_approval/KegiatanSendApproval" --data-urlencode "KegiatanID=$KID" >/dev/null
check "$(MY "SELECT KegiatanStatus FROM tb_kegiatan WHERE KegiatanID=$KID")" "Approval OnProgress" "diajukan"

step() { local u="$1" fc="$2" want="$3" lbl="$4"
  login "$u" >/dev/null
  post "$u" "manajemen_approval/ApprovalFormInfoKegiatanSubmit" \
    --data-urlencode "KegiatanID=$KID" --data-urlencode "FlowCode=$fc" --data-urlencode "FlowResult=1" \
    --data-urlencode "FlowRejectType=revisi" --data-urlencode "FlowKeterangan=[smoke] ok" \
    --data-urlencode "KegiatanNoKwitansi=KW$STAMP" --data-urlencode "KegiatanNoSPTJB=SP$STAMP" >/dev/null
  check "$(MY "SELECT KegiatanStatusTerakhir FROM tb_kegiatan WHERE KegiatanID=$KID")" "$want" "$lbl"
}
step stafppk PPKS1 PPKS1 "Staff PPK setuju"
step spm1    SPM1  SPM1  "SPM setuju"
step verif1  VRF2  VRF2  "Verifikator setuju"
step ppk1    PPK1  PPK1  "PPK setuju"
step ppspm   SLS   SLS   "PPSPM setuju"
check "$(MY "SELECT KegiatanStatus FROM tb_kegiatan WHERE KegiatanID=$KID")" "Approval Selesai" "status akhir = Approval Selesai"
check "$(MY "SELECT COUNT(*) FROM tb_approval_history WHERE KegiatanID=$KID")" "6" "6 baris riwayat"

echo "== CSRF aktif? (POST tanpa token harus ditolak) =="
NOCSRF=$(curl -sS -b "$(jar PJ1)" -o /dev/null -w '%{http_code}' \
  --data-urlencode "KegiatanID=$KID" "$BASE/manajemen_approval/KegiatanSendApproval")
check "$NOCSRF" "403" "POST tanpa token CSRF -> 403"

echo "== bersih-bersih kegiatan uji =="
MYT "DELETE FROM tb_kegiatan WHERE KegiatanID=$KID; DELETE FROM tb_approval_history WHERE KegiatanID=$KID; DELETE FROM tb_wa_log WHERE KegiatanID=$KID; DELETE FROM tb_dokumen WHERE KegiatanID=$KID;" >/dev/null

echo; [ "$FAIL" = "0" ] && echo "SMOKE TEST: SEMUA LULUS" || echo "SMOKE TEST: ADA YANG GAGAL"
exit $FAIL
