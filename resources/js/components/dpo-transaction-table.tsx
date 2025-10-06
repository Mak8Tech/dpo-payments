import React from "react";
import {
  AlertCircle,
  Check,
  X,
  Loader2,
  CreditCard,
  Smartphone,
} from "lucide-react";

type TransactionStatus =
  | "success"
  | "pending"
  | "processing"
  | "failed"
  | "cancelled"
  | "refunded";

interface Transaction {
  id: string;
  reference: string;
  customer_name?: string;
  customer_email?: string;
  formatted_amount: string;
  status: TransactionStatus;
  created_at: string;
  amount: number;
  refunded_amount: number;
}

interface DpoTransactionTableProps {
  transactions?: Transaction[];
  onRefund?: (transaction: Transaction) => void;
  onCancel?: (transaction: Transaction) => void;
}

export const DpoTransactionTable: React.FC<DpoTransactionTableProps> = ({
  transactions = [],
  onRefund,
  onCancel,
}) => {
  const getStatusColor = (status: TransactionStatus): string => {
    const colors: Record<TransactionStatus, string> = {
      success: "bg-green-100 text-green-800",
      pending: "bg-yellow-100 text-yellow-800",
      processing: "bg-blue-100 text-blue-800",
      failed: "bg-red-100 text-red-800",
      cancelled: "bg-gray-100 text-gray-800",
      refunded: "bg-purple-100 text-purple-800",
    };
    return colors[status] || "bg-gray-100 text-gray-800";
  };

  return (
    <div className="overflow-x-auto">
      <table className="min-w-full bg-white">
        <thead className="bg-gray-50">
          <tr>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Reference
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Customer
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Amount
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Status
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Date
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Actions
            </th>
          </tr>
        </thead>
        <tbody className="bg-white divide-y divide-gray-200">
          {transactions.map((transaction) => (
            <tr key={transaction.id}>
              <td className="px-6 py-4 whitespace-nowrap text-sm">
                <code className="text-gray-900">{transaction.reference}</code>
              </td>
              <td className="px-6 py-4 whitespace-nowrap text-sm">
                <div className="text-gray-900">
                  {transaction.customer_name || "-"}
                </div>
                <div className="text-gray-500">
                  {transaction.customer_email || "-"}
                </div>
              </td>
              <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                {transaction.formatted_amount}
              </td>
              <td className="px-6 py-4 whitespace-nowrap">
                <span
                  className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusColor(
                    transaction.status
                  )}`}
                >
                  {transaction.status}
                </span>
              </td>
              <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {new Date(transaction.created_at).toLocaleDateString()}
              </td>
              <td className="px-6 py-4 whitespace-nowrap text-sm">
                {transaction.status === "success" &&
                  transaction.refunded_amount < transaction.amount && (
                    <button
                      onClick={() => onRefund?.(transaction)}
                      className="text-indigo-600 hover:text-indigo-900 mr-3"
                    >
                      Refund
                    </button>
                  )}
                {transaction.status === "pending" && (
                  <button
                    onClick={() => onCancel?.(transaction)}
                    className="text-red-600 hover:text-red-900"
                  >
                    Cancel
                  </button>
                )}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};
